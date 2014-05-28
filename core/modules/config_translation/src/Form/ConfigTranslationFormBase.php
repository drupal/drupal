<?php

/**
 * @file
 * Contains \Drupal\config_translation\Form\ConfigTranslationFormBase.
 */

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\Language;
use Drupal\language\Config\LanguageConfigOverride;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for configuration translations.
 */
abstract class ConfigTranslationFormBase extends FormBase implements BaseFormIdInterface {

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The string translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * The language of the configuration translation source.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $sourceLanguage;

  /**
   * An array of base language configuration data keyed by configuration names.
   *
   * @var array
   */
  protected $baseConfigData = array();

  /**
   * Creates manage form object with string translation storage.
   *
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\locale\StringStorageInterface $locale_storage
   *   The translation storage object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook.
   */
  public function __construct(TypedConfigManager $typed_config_manager, ConfigMapperManagerInterface $config_mapper_manager, StringStorageInterface $locale_storage, ModuleHandlerInterface $module_handler, ConfigurableLanguageManagerInterface $language_manager) {
    $this->typedConfigManager = $typed_config_manager;
    $this->configMapperManager = $config_mapper_manager;
    $this->localeStorage = $locale_storage;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.typed'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('locale.storage'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
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
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (optional) Page request object.
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
  public function buildForm(array $form, array &$form_state, Request $request = NULL, $plugin_id = NULL, $langcode = NULL) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRequest($request);

    $language = language_load($langcode);
    if (!$language) {
      throw new NotFoundHttpException();
    }

    $this->mapper = $mapper;
    $this->language = $language;
    $this->sourceLanguage = $this->mapper->getLanguageWithFallback();

    // Get base language configuration to display in the form before setting the
    // language to use for the form. This avoids repetitively settings and
    // resetting the language to get original values later.
    $config_factory = $this->configFactory();
    $old_state = $config_factory->getOverrideState();
    $config_factory->setOverrideState(FALSE);
    $this->baseConfigData = $this->mapper->getConfigData();
    $config_factory->setOverrideState($old_state);

    // Set the translation target language on the configuration factory.
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($this->language);

    // Add some information to the form state for easier form altering.
    $form_state['config_translation_mapper'] = $this->mapper;
    $form_state['config_translation_language'] = $this->language;
    $form_state['config_translation_source_language'] = $this->sourceLanguage;

    $form['#attached']['library'][] = 'config_translation/drupal.config_translation.admin';

    $form['config_names'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );
    foreach ($this->mapper->getConfigNames() as $name) {
      $form['config_names'][$name] = array('#type' => 'container');
      $form['config_names'][$name] += $this->buildConfigForm($this->typedConfigManager->get($name), $config_factory->get($name)->get(), $this->baseConfigData[$name]);
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save translation'),
      '#button_type' => 'primary',
    );

    // Set the configuration language back.
    $this->languageManager->setConfigOverrideLanguage($original_language);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_values = $form_state['values']['config_names'];

    // For the form submission handling, use the raw data.
    $config_factory = $this->configFactory();
    $old_state = $config_factory->getOverrideState();
    $config_factory->setOverrideState(FALSE);

    foreach ($this->mapper->getConfigNames() as $name) {
      // Set configuration values based on form submission and source values.
      $base_config = $config_factory->get($name);
      $config_translation = $this->languageManager->getLanguageConfigOverride($this->language->id, $name);
      $locations = $this->localeStorage->getLocations(array('type' => 'configuration', 'name' => $name));

      $this->setConfig($this->language, $base_config, $config_translation, $form_values[$name], !empty($locations));

      // If no overrides, delete language specific configuration file.
      $saved_config = $config_translation->get();
      if (empty($saved_config)) {
        $config_translation->delete();
      }
      else {
        $config_translation->save();
      }
    }
    $config_factory->setOverrideState($old_state);

    $form_state['redirect_route'] = array(
      'route_name' => $this->mapper->getOverviewRoute(),
      'route_parameters' => $this->mapper->getOverviewRouteParameters(),
    );
  }

  /**
   * Formats configuration schema as a form tree.
   *
   * @param \Drupal\Core\Config\Schema\Element $schema
   *   Schema definition of configuration.
   * @param array|string $config_data
   *   Configuration object of requested language, a string when done traversing
   *   the data building each sub-structure for the form.
   * @param array|string $base_config_data
   *   Configuration object of base language, a string when done traversing
   *   the data building each sub-structure for the form.
   * @param bool $open
   *   (optional) Whether or not the details element of the form should be open.
   *   Defaults to TRUE.
   * @param string|null $base_key
   *   (optional) Base configuration key. Defaults to an empty string.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  protected function buildConfigForm(Element $schema, $config_data, $base_config_data, $open = TRUE, $base_key = '') {
    $build = array();
    foreach ($schema as $key => $element) {
      // Make the specific element key, "$base_key.$key".
      $element_key = implode('.', array_filter(array($base_key, $key)));
      $definition = $element->getDataDefinition() + array('label' => $this->t('N/A'));
      if ($element instanceof Element) {
        // Build sub-structure and include it with a wrapper in the form
        // if there are any translatable elements there.
        $sub_build = $this->buildConfigForm($element, $config_data[$key], $base_config_data[$key], FALSE, $element_key);
        if (!empty($sub_build)) {
          // For some configuration elements the same element structure can
          // repeat multiple times, (like views displays, filters, etc.).
          // So try to find a more usable title for the details summary. First
          // check if there is an element which is called title or label, then
          // check if there is an element which contains these words.
          $title = '';
          if (isset($sub_build['title']['source'])) {
            $title = $sub_build['title']['source']['#markup'];
          }
          elseif (isset($sub_build['label']['source'])) {
            $title = $sub_build['label']['source']['#markup'];
          }
          else {
            foreach (array_keys($sub_build) as $title_key) {
              if (isset($sub_build[$title_key]['source']) && (strpos($title_key, 'title') !== FALSE || strpos($title_key, 'label') !== FALSE)) {
                $title = $sub_build[$title_key]['source']['#markup'];
                break;
              }
            }
          }
          $build[$key] = array(
            '#type' => 'details',
            '#title' => (!empty($title) ? (strip_tags($title) . ' ') : '') . $this->t($definition['label']),
            '#open' => $open,
          ) + $sub_build;
        }
      }
      else {
        $definition = $element->getDataDefinition();

        // Invoke hook_config_translation_type_info_alter() implementations to
        // alter the configuration types.
        $definitions = array(
          $definition['type'] => &$definition,
        );
        $this->moduleHandler->alter('config_translation_type_info', $definitions);

        // Create form element only for translatable items.
        if (!isset($definition['translatable']) || !isset($definition['type'])) {
          continue;
        }

        $value = $config_data[$key];
        $build[$element_key] = array(
          '#theme' => 'config_translation_manage_form_element',
        );
        $build[$element_key]['source'] = array(
          '#markup' => $base_config_data[$key] ? ('<span lang="' . $this->sourceLanguage->id . '">' . nl2br($base_config_data[$key] . '</span>')) : t('(Empty)'),
          '#title' => $this->t(
            '!label <span class="visually-hidden">(!source_language)</span>',
            array(
              '!label' => $this->t($definition['label']),
              '!source_language' => $this->sourceLanguage->name,
            )
          ),
          '#type' => 'item',
        );

        $definition += array('form_element_class' => '\Drupal\config_translation\FormElement\Textfield');

        /** @var \Drupal\config_translation\FormElement\ElementInterface $form_element */
        $form_element = new $definition['form_element_class']();
        $build[$element_key]['translation'] = $form_element->getFormElement($definition, $this->language, $value);
      }
    }
    return $build;
  }

  /**
   * Sets configuration based on a nested form value array.
   *
   * @param \Drupal\Core\Language\Language $language
   *   Set the configuration in this language.
   * @param \Drupal\Core\Config\Config $base_config
   *   Base configuration values, in the source language.
   * @param \Drupal\language\Config\LanguageConfigOverride $config_translation
   *   Translation configuration override data.
   * @param array $config_values
   *   A simple one dimensional or recursive array:
   *     - simple:
   *        array(name => array('translation' => 'French site name'));
   *     - recursive:
   *        cancel_confirm => array(
   *          cancel_confirm.subject => array('translation' => 'Subject'),
   *          cancel_confirm.body => array('translation' => 'Body content'),
   *        );
   *   Either format is used, the nested arrays are just containers and not
   *   needed for saving the data.
   * @param bool $shipped_config
   *   (optional) Flag to specify whether the configuration had a shipped
   *   version and therefore should also be stored in the locale database.
   *
   * @return array
   *   Translation configuration override data.
   */
  protected function setConfig(Language $language, Config $base_config, LanguageConfigOverride $config_translation, array $config_values, $shipped_config = FALSE) {
    foreach ($config_values as $key => $value) {
      if (is_array($value) && !isset($value['translation'])) {
        // Traverse into this level in the configuration.
        $this->setConfig($language, $base_config, $config_translation, $value, $shipped_config);
      }
      else {

        // If the configuration file being translated was originally shipped, we
        // should update the locale translation storage. The string should
        // already be there, but we make sure to check.
        if ($shipped_config && $source_string = $this->localeStorage->findString(array('source' => $base_config->get($key)))) {

          // Get the translation for this original source string from locale.
          $conditions = array(
            'lid' => $source_string->lid,
            'language' => $language->id,
          );
          $translations = $this->localeStorage->getTranslations($conditions + array('translated' => TRUE));
          // If we got a translation, take that, otherwise create a new one.
          $translation = reset($translations) ?: $this->localeStorage->createTranslation($conditions);

          // If we have a new translation or different from what is stored in
          // locale before, save this as an updated customize translation.
          if ($translation->isNew() || $translation->getString() != $value['translation']) {
            $translation->setString($value['translation'])
              ->setCustomized()
              ->save();
          }
        }

        // Save value, if different from the source value in the base
        // configuration. If same as original configuration, remove override.
        if ($base_config->get($key) !== $value['translation']) {
          $config_translation->set($key, $value['translation']);
        }
        else {
          $config_translation->clear($key);
        }
      }
    }
  }

}
