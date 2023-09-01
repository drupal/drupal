<?php

namespace Drupal\language\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the browser language negotiation method for this site.
 *
 * @internal
 */
class NegotiationBrowserForm extends ConfigFormBase {

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, ConfigurableLanguageManagerInterface $language_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_browser_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.mappings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    // Initialize a language list to the ones available, including English.
    $languages = $this->languageManager->getLanguages();

    $existing_languages = [];
    foreach ($languages as $langcode => $language) {
      $existing_languages[$langcode] = $language->getName();
    }

    // If we have no languages available, present the list of predefined languages
    // only. If we do have already added languages, set up two option groups with
    // the list of existing and then predefined languages.
    if (empty($existing_languages)) {
      $language_options = $this->languageManager->getStandardLanguageListWithoutConfigured();
    }
    else {
      $language_options = [
        (string) $this->t('Existing languages') => $existing_languages,
        (string) $this->t('Languages not yet added') => $this->languageManager->getStandardLanguageListWithoutConfigured(),
      ];
    }

    $form['mappings'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Browser language code'),
        $this->t('Site language'),
        $this->t('Operations'),
      ],
      '#attributes' => ['id' => 'language-negotiation-browser'],
      '#empty' => $this->t('No browser language mappings available.'),
    ];

    $mappings = $this->language_get_browser_drupal_langcode_mappings();
    foreach ($mappings as $browser_langcode => $drupal_langcode) {
      $form['mappings'][$browser_langcode] = [
        'browser_langcode' => [
          '#title' => $this->t('Browser language code'),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => $browser_langcode,
          '#size' => 20,
          '#required' => TRUE,
        ],
        'drupal_langcode' => [
          '#title' => $this->t('Site language'),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#options' => $language_options,
          '#default_value' => $drupal_langcode,
          '#required' => TRUE,
        ],
      ];
      // Operations column.
      $form['mappings'][$browser_langcode]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $form['mappings'][$browser_langcode]['operations']['#links']['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('language.negotiation_browser_delete', ['browser_langcode' => $browser_langcode]),
      ];
    }

    // Add empty row.
    $form['new_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Add a new mapping'),
      '#tree' => TRUE,
    ];
    $form['new_mapping']['browser_langcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Browser language code'),
      '#description' => $this->t('Use language codes as <a href=":w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', [':w3ctags' => 'http://www.w3.org/International/articles/language-tags/']),
      '#size' => 20,
    ];
    $form['new_mapping']['drupal_langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Site language'),
      '#options' => $language_options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Array to check if all browser language codes are unique.
    $unique_values = [];

    // Check all mappings.
    if ($form_state->hasValue('mappings')) {
      $mappings = $form_state->getValue('mappings');
      foreach ($mappings as $key => $data) {
        // Make sure browser_langcode is unique.
        if (array_key_exists($data['browser_langcode'], $unique_values)) {
          $form_state->setErrorByName('mappings][new_mapping][browser_langcode', $this->t('Browser language codes must be unique.'));
        }
        elseif (preg_match('/[^a-z\-]/', $data['browser_langcode'])) {
          $form_state->setErrorByName('mappings][new_mapping][browser_langcode', $this->t('Browser language codes can only contain lowercase letters and a hyphen(-).'));
        }
        $unique_values[$data['browser_langcode']] = $data['drupal_langcode'];
      }
    }

    // Check new mapping.
    $data = $form_state->getValue('new_mapping');
    if (!empty($data['browser_langcode'])) {
      // Make sure browser_langcode is unique.
      if (array_key_exists($data['browser_langcode'], $unique_values)) {
        $form_state->setErrorByName('mappings][' . $key . '][browser_langcode', $this->t('Browser language codes must be unique.'));
      }
      elseif (preg_match('/[^a-z\-]/', $data['browser_langcode'])) {
        $form_state->setErrorByName('mappings][' . $key . '][browser_langcode', $this->t('Browser language codes can only contain lowercase letters and a hyphen(-).'));
      }
      $unique_values[$data['browser_langcode']] = $data['drupal_langcode'];
    }

    $form_state->set('mappings', $unique_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->get('mappings');
    if (!empty($mappings)) {
      $config = $this->config('language.mappings');
      $config->setData(['map' => $mappings]);
      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Retrieves the browser's langcode mapping configuration array.
   *
   * @return array
   *   The browser's langcode mapping configuration array.
   */
  protected function language_get_browser_drupal_langcode_mappings() {
    $config = $this->config('language.mappings');
    if ($config->isNew()) {
      return [];
    }
    return $config->get('map');
  }

}
