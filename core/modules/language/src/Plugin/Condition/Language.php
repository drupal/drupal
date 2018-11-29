<?php

namespace Drupal\language\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language' condition.
 *
 * @Condition(
 *   id = "language",
 *   label = @Translation("Language"),
 *   context_definitions = {
 *     "language" = @ContextDefinition("language", label = @Translation("Language"))
 *   }
 * )
 */
class Language extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a new Language instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(LanguageManagerInterface $language_manager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('language_manager'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->languageManager->isMultilingual()) {
      // Fetch languages.
      $languages = $this->languageManager->getLanguages();
      $langcodes_options = [];
      foreach ($languages as $language) {
        $langcodes_options[$language->getId()] = $language->getName();
      }
      $form['langcodes'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Language selection'),
        '#default_value' => $this->configuration['langcodes'],
        '#options' => $langcodes_options,
        '#description' => $this->t('Select languages to enforce. If none are selected, all languages will be allowed.'),
      ];
    }
    else {
      $form['langcodes'] = [
        '#type' => 'value',
        '#default_value' => $this->configuration['langcodes'],
      ];
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['langcodes'] = array_filter($form_state->getValue('langcodes'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $language_list = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    $selected = $this->configuration['langcodes'];
    // Reduce the language list to an array of language names.
    $language_names = array_reduce($language_list, function (&$result, $item) use ($selected) {
      // If the current item of the $language_list array is one of the selected
      // languages, add it to the $results array.
      if (!empty($selected[$item->getId()])) {
        $result[$item->getId()] = $item->getName();
      }
      return $result;
    }, []);

    // If we have more than one language selected, separate them by commas.
    if (count($this->configuration['langcodes']) > 1) {
      $languages = implode(', ', $language_names);
    }
    else {
      // If we have just one language just grab the only present value.
      $languages = array_pop($language_names);
    }
    if (!empty($this->configuration['negate'])) {
      return t('The language is not @languages.', ['@languages' => $languages]);
    }
    return t('The language is @languages.', ['@languages' => $languages]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['langcodes']) && !$this->isNegated()) {
      return TRUE;
    }

    $language = $this->getContextValue('language');
    // Language visibility settings.
    return !empty($this->configuration['langcodes'][$language->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['langcodes' => []] + parent::defaultConfiguration();
  }

}
