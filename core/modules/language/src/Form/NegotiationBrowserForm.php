<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationBrowserForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the browser language negotiation method for this site.
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
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigurableLanguageManagerInterface $language_manager ) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    // Initialize a language list to the ones available, including English.
    $languages = language_list();

    $existing_languages = array();
    foreach ($languages as $langcode => $language) {
      $existing_languages[$langcode] = $language->name;
    }

    // If we have no languages available, present the list of predefined languages
    // only. If we do have already added languages, set up two option groups with
    // the list of existing and then predefined languages.
    if (empty($existing_languages)) {
      $language_options = $this->languageManager->getStandardLanguageListWithoutConfigured();
    }
    else {
      $language_options = array(
        $this->t('Existing languages') => $existing_languages,
        $this->t('Languages not yet added') => $this->languageManager->getStandardLanguageListWithoutConfigured(),
      );
    }

    $form['mappings'] = array(
      '#tree' => TRUE,
      '#theme' => 'language_negotiation_configure_browser_form_table',
    );

    $mappings = $this->language_get_browser_drupal_langcode_mappings();
    foreach ($mappings as $browser_langcode => $drupal_langcode) {
      $form['mappings'][$browser_langcode] = array(
        'browser_langcode' => array(
          '#type' => 'textfield',
          '#default_value' => $browser_langcode,
          '#size' => 20,
          '#required' => TRUE,
        ),
        'drupal_langcode' => array(
          '#type' => 'select',
          '#options' => $language_options,
          '#default_value' => $drupal_langcode,
          '#required' => TRUE,
        ),
      );
    }

    // Add empty row.
    $form['new_mapping'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add a new mapping'),
      '#tree' => TRUE,
    );
    $form['new_mapping']['browser_langcode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Browser language code'),
      '#description' => $this->t('Use language codes as <a href="@w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array('@w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
      '#default_value' => '',
      '#size' => 20,
    );
    $form['new_mapping']['drupal_langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Drupal language'),
      '#options' => $language_options,
      '#default_value' => '',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Array to check if all browser language codes are unique.
    $unique_values = array();

    // Check all mappings.
    $mappings = array();
    if (isset($form_state['values']['mappings'])) {
      $mappings = $form_state['values']['mappings'];
      foreach ($mappings as $key => $data) {
        // Make sure browser_langcode is unique.
        if (array_key_exists($data['browser_langcode'], $unique_values)) {
          $form_state->setErrorByName('mappings][' . $key . '][browser_langcode', $this->t('Browser language codes must be unique.'));
        }
        elseif (preg_match('/[^a-z\-]/', $data['browser_langcode'])) {
          $form_state->setErrorByName('mappings][' . $key . '][browser_langcode', $this->t('Browser language codes can only contain lowercase letters and a hyphen(-).'));
        }
        $unique_values[$data['browser_langcode']] = $data['drupal_langcode'];
      }
    }

    // Check new mapping.
    $data = $form_state['values']['new_mapping'];
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

    $form_state['mappings'] = $unique_values;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state['mappings'];
    if (!empty($mappings)) {
      $config = $this->config('language.mappings');
      $config->setData($mappings);
      $config->save();
    }
    $form_state->setRedirect('language.negotiation');

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
      return array();
    }
    return $config->get();
  }
}

