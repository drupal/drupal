<?php

namespace Drupal\locale\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form constructor for the translation import screen.
 *
 * @internal
 */
class ImportForm extends FormBase {

  /**
   * Uploaded file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * Constructs a form for language import.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigurableLanguageManagerInterface $language_manager) {
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translate_import_form';
  }

  /**
   * Form constructor for the translation import screen.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();

    // Initialize a language list to the ones available, including English if we
    // are to translate Drupal to English as well.
    $existing_languages = [];
    foreach ($languages as $langcode => $language) {
      if (locale_is_translatable($langcode)) {
        $existing_languages[$langcode] = $language->getName();
      }
    }

    // If we have no languages available, present the list of predefined
    // languages only. If we do have already added languages, set up two option
    // groups with the list of existing and then predefined languages.
    if (empty($existing_languages)) {
      $language_options = $this->languageManager->getStandardLanguageListWithoutConfigured();
      $default = key($language_options);
    }
    else {
      $default = key($existing_languages);
      $language_options = [
        (string) $this->t('Existing languages') => $existing_languages,
        (string) $this->t('Languages not yet added') => $this->languageManager->getStandardLanguageListWithoutConfigured(),
      ];
    }

    $validators = [
      'FileExtension' => ['extensions' => 'po'],
      'FileSizeLimit' => ['fileLimit' => Environment::getUploadMaxSize()],
      'FileEncoding' => ['encodings' => ['UTF-8']],
    ];
    $form['file'] = [
      '#type' => 'file',
      '#title' => $this->t('Translation file'),
      '#description' => [
        '#theme' => 'file_upload_help',
        '#description' => $this->t('A Gettext Portable Object file.'),
        '#upload_validators' => $validators,
      ],
      '#size' => 50,
      '#upload_validators' => $validators,
      '#upload_location' => 'translations://',
      '#attributes' => ['class' => ['file-import-input']],
    ];
    $form['langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $language_options,
      '#default_value' => $default,
      '#attributes' => ['class' => ['langcode-input']],
    ];

    $form['customized'] = [
      '#title' => $this->t('Treat imported strings as custom translations'),
      '#type' => 'checkbox',
    ];
    $form['overwrite_options'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['overwrite_options']['not_customized'] = [
      '#title' => $this->t('Overwrite non-customized translations'),
      '#type' => 'checkbox',
      '#states' => [
        'checked' => [
          ':input[name="customized"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['overwrite_options']['customized'] = [
      '#title' => $this->t('Overwrite existing customized translations'),
      '#type' => 'checkbox',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->file = _file_save_upload_from_form($form['file'], $form_state, 0);

    // Ensure we have the file uploaded.
    if (!$this->file) {
      $form_state->setErrorByName('file', $this->t('File to import not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('locale', 'translation.inc');
    // Add language, if not yet supported.
    $language = $this->languageManager->getLanguage($form_state->getValue('langcode'));
    if (empty($language)) {
      $language = ConfigurableLanguage::createFromLangcode($form_state->getValue('langcode'));
      $language->save();
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $this->messenger()->addStatus($this->t('The language %language has been created.', ['%language' => $this->t($language->label())]));
    }
    $options = array_merge(_locale_translation_default_update_options(), [
      'langcode' => $form_state->getValue('langcode'),
      'overwrite_options' => $form_state->getValue('overwrite_options'),
      'customized' => $form_state->getValue('customized') ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED,
    ]);
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');
    $file = locale_translate_file_attach_properties($this->file, $options);
    $batch = locale_translate_batch_build([$file->uri => $file], $options);
    batch_set($batch);

    // Create or update all configuration translations for this language.
    if ($batch = locale_config_batch_update_components($options, [$form_state->getValue('langcode')])) {
      batch_set($batch);
    }

    $form_state->setRedirect('locale.translate_page');
  }

}
