<?php

namespace Drupal\locale\Form;

use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\PoDatabaseReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Form for the Gettext translation files export form.
 */
class ExportForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ExportForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translate_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();
    $language_options = array();
    foreach ($languages as $langcode => $language) {
      if (locale_is_translatable($langcode)) {
        $language_options[$langcode] = $language->getName();
      }
    }
    $language_default = $this->languageManager->getDefaultLanguage();

    if (empty($language_options)) {
      $form['langcode'] = array(
        '#type' => 'value',
        '#value' => LanguageInterface::LANGCODE_SYSTEM,
      );
      $form['langcode_text'] = array(
        '#type' => 'item',
        '#title' => $this->t('Language'),
        '#markup' => $this->t('No language available. The export will only contain source strings.'),
      );
    }
    else {
      $form['langcode'] = array(
        '#type' => 'select',
        '#title' => $this->t('Language'),
        '#options' => $language_options,
        '#default_value' => $language_default->getId(),
        '#empty_option' => $this->t('Source text only, no translations'),
        '#empty_value' => LanguageInterface::LANGCODE_SYSTEM,
      );
      $form['content_options'] = array(
        '#type' => 'details',
        '#title' => $this->t('Export options'),
        '#collapsed' => TRUE,
        '#tree' => TRUE,
        '#states' => array(
          'invisible' => array(
            ':input[name="langcode"]' => array('value' => LanguageInterface::LANGCODE_SYSTEM),
          ),
        ),
      );
      $form['content_options']['not_customized'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Include non-customized translations'),
        '#default_value' => TRUE,
      );
      $form['content_options']['customized'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Include customized translations'),
        '#default_value' => TRUE,
      );
      $form['content_options']['not_translated'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Include untranslated text'),
        '#default_value' => TRUE,
      );
    }

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If template is required, language code is not given.
    if ($form_state->getValue('langcode') != LanguageInterface::LANGCODE_SYSTEM) {
      $language = $this->languageManager->getLanguage($form_state->getValue('langcode'));
    }
    else {
      $language = NULL;
    }
    $content_options = $form_state->getValue('content_options', array());
    $reader = new PoDatabaseReader();
    $language_name = '';
    if ($language != NULL) {
      $reader->setLangcode($language->getId());
      $reader->setOptions($content_options);
      $languages = $this->languageManager->getLanguages();
      $language_name = isset($languages[$language->getId()]) ? $languages[$language->getId()]->getName() : '';
      $filename = $language->getId() . '.po';
    }
    else {
      // Template required.
      $filename = 'drupal.pot';
    }

    $item = $reader->readItem();
    if (!empty($item)) {
      $uri = tempnam('temporary://', 'po_');
      $header = $reader->getHeader();
      $header->setProjectName($this->config('system.site')->get('name'));
      $header->setLanguageName($language_name);

      $writer = new PoStreamWriter();
      $writer->setUri($uri);
      $writer->setHeader($header);

      $writer->open();
      $writer->writeItem($item);
      $writer->writeItems($reader);
      $writer->close();

      $response = new BinaryFileResponse($uri);
      $response->setContentDisposition('attachment', $filename);
      $form_state->setResponse($response);
    }
    else {
      drupal_set_message($this->t('Nothing to export.'));
    }
  }

}
