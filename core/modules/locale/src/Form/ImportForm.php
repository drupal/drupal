<?php

/**
 * @file
 * Contains \Drupal\locale\Form\ImportForm.
 */

namespace Drupal\locale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form constructor for the translation import screen.
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
  public function getFormID() {
    return 'locale_translate_import_form';
  }

  /**
   * Form constructor for the translation import screen.
   *
   * @ingroup forms
   */
  public function buildForm(array $form, array &$form_state) {
    $languages = $this->languageManager->getLanguages();

    // Initialize a language list to the ones available, including English if we
    // are to translate Drupal to English as well.
    $existing_languages = array();
    foreach ($languages as $langcode => $language) {
      if ($langcode != 'en' || locale_translate_english()) {
        $existing_languages[$langcode] = $language->name;
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
      $language_options = array(
        $this->t('Existing languages') => $existing_languages,
        $this->t('Languages not yet added') => $this->languageManager->getStandardLanguageListWithoutConfigured(),
      );
    }

    $validators = array(
      'file_validate_extensions' => array('po'),
      'file_validate_size' => array(file_upload_max_size()),
    );
    $form['file'] = array(
      '#type' => 'file',
      '#title' => $this->t('Translation file'),
      '#description' => array(
        '#theme' => 'file_upload_help',
        '#description' => $this->t('A Gettext Portable Object file.'),
        '#upload_validators' => $validators,
       ),
      '#size' => 50,
      '#upload_validators' => $validators,
      '#attributes' => array('class' => array('file-import-input')),
    );
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $language_options,
      '#default_value' => $default,
      '#attributes' => array('class' => array('langcode-input')),
    );

    $form['customized'] = array(
      '#title' => $this->t('Treat imported strings as custom translations'),
      '#type' => 'checkbox',
    );
    $form['overwrite_options'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );
    $form['overwrite_options']['not_customized'] = array(
      '#title' => $this->t('Overwrite non-customized translations'),
      '#type' => 'checkbox',
      '#states' => array(
        'checked' => array(
          ':input[name="customized"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['overwrite_options']['customized'] = array(
      '#title' => $this->t('Overwrite existing customized translations'),
      '#type' => 'checkbox',
    );

    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $this->file = file_save_upload('file', $form['file']['#upload_validators'], 'translations://', 0);

    // Ensure we have the file uploaded.
    if (!$this->file) {
      $this->setFormError('file', $form_state, $this->t('File to import not found.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Add language, if not yet supported.
    $language = $this->languageManager->getLanguage($form_state['values']['langcode']);
    if (empty($language)) {
      $language = new Language(array(
        'id' => $form_state['values']['langcode']
      ));
      $language = language_save($language);
      drupal_set_message($this->t('The language %language has been created.', array('%language' => $this->t($language->name))));
    }
    $options = array(
      'langcode' => $form_state['values']['langcode'],
      'overwrite_options' => $form_state['values']['overwrite_options'],
      'customized' => $form_state['values']['customized'] ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED,
    );
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');
    $file = locale_translate_file_attach_properties($this->file, $options);
    $batch = locale_translate_batch_build(array($file->uri => $file), $options);
    batch_set($batch);

    $form_state['redirect_route']['route_name'] = 'locale.translate_page';
  }
}
