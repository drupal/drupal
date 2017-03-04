<?php

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for configuration translations.
 */
abstract class ConfigTranslationFormBase extends FormBase implements BaseFormIdInterface {

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The mapper for configuration translation.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface
   */
  protected $mapper;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language of the configuration translation.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * The language of the configuration translation source.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $sourceLanguage;

  /**
   * An array of base language configuration data keyed by configuration names.
   *
   * @var array
   */
  protected $baseConfigData = [];

  /**
   * Constructs a ConfigTranslationFormBase.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   */
  public function __construct(TypedConfigManagerInterface $typed_config_manager, ConfigMapperManagerInterface $config_mapper_manager, ConfigurableLanguageManagerInterface $language_manager) {
    $this->typedConfigManager = $typed_config_manager;
    $this->configMapperManager = $config_mapper_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.typed'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'config_translation_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Builds configuration form with metadata and values from the source
   * language.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   (optional) The route match.
   * @param string $plugin_id
   *   (optional) The plugin ID of the mapper.
   * @param string $langcode
   *   (optional) The language code of the language the form is adding or
   *   editing.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if the language code provided as a query parameter in
   *   the request does not match an active language.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $plugin_id = NULL, $langcode = NULL) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRouteMatch($route_match);

    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      throw new NotFoundHttpException();
    }

    $this->mapper = $mapper;
    $this->language = $language;

    // ConfigTranslationFormAccess will not grant access if this raises an
    // exception, so we can call this without a try-catch block here.
    $langcode = $this->mapper->getLangcode();

    $this->sourceLanguage = $this->languageManager->getLanguage($langcode);

    // Get base language configuration to display in the form before setting the
    // language to use for the form. This avoids repetitively settings and
    // resetting the language to get original values later.
    $this->baseConfigData = $this->mapper->getConfigData();

    // Set the translation target language on the configuration factory.
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($this->language);

    // Add some information to the form state for easier form altering.
    $form_state->set('config_translation_mapper', $this->mapper);
    $form_state->set('config_translation_language', $this->language);
    $form_state->set('config_translation_source_language', $this->sourceLanguage);

    $form['#attached']['library'][] = 'config_translation/drupal.config_translation.admin';

    // Even though this is a nested form, we do not set #tree to TRUE because
    // the form value structure is generated by using #parents for each element.
    // @see \Drupal\config_translation\FormElement\FormElementBase::getElements()
    $form['config_names'] = ['#type' => 'container'];
    foreach ($this->mapper->getConfigNames() as $name) {
      $form['config_names'][$name] = ['#type' => 'container'];

      $schema = $this->typedConfigManager->get($name);
      $source_config = $this->baseConfigData[$name];
      $translation_config = $this->configFactory()->get($name)->get();

      if ($form_element = $this->createFormElement($schema)) {
        $parents = ['config_names', $name];
        $form['config_names'][$name] += $form_element->getTranslationBuild($this->sourceLanguage, $this->language, $source_config, $translation_config, $parents);
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save translation'),
      '#button_type' => 'primary',
    ];

    // Set the configuration language back.
    $this->languageManager->setConfigOverrideLanguage($original_language);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValue(['translation', 'config_names']);

    foreach ($this->mapper->getConfigNames() as $name) {
      $schema = $this->typedConfigManager->get($name);

      // Set configuration values based on form submission and source values.
      $base_config = $this->configFactory()->getEditable($name);
      $config_translation = $this->languageManager->getLanguageConfigOverride($this->language->getId(), $name);

      $element = $this->createFormElement($schema);
      $element->setConfig($base_config, $config_translation, $form_values[$name]);

      // If no overrides, delete language specific configuration file.
      $saved_config = $config_translation->get();
      if (empty($saved_config)) {
        $config_translation->delete();
      }
      else {
        $config_translation->save();
      }
    }

    $form_state->setRedirect(
      $this->mapper->getOverviewRoute(),
      $this->mapper->getOverviewRouteParameters()
    );
  }

  /**
   * Creates a form element builder.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $schema
   *   Schema definition of configuration.
   *
   * @return \Drupal\config_translation\FormElement\ElementInterface|null
   *   The element builder object if possible.
   */
  public static function createFormElement(TypedDataInterface $schema) {
    $definition = $schema->getDataDefinition();
    // Form element classes can be specified even for non-translatable elements
    // such as the ListElement form element which is used for Mapping and
    // Sequence schema elements.
    if (isset($definition['form_element_class'])) {
      if (!$definition->getLabel()) {
        $definition->setLabel(t('n/a'));
      }
      $class = $definition['form_element_class'];
      return $class::create($schema);
    }
  }

}
