<?php

declare(strict_types=1);

namespace Drupal\file_test\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File test form class.
 */
class FileTestSaveUploadFromForm extends FormBase {
  use FileTestFormTrait;

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a FileTestSaveUploadFromForm object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(StateInterface $state, MessengerInterface $messenger) {
    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_file_test_save_upload_from_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = $this->baseForm($form, $form_state);

    $form['file_test_upload'] = [
      '#type' => 'file',
      '#multiple' => TRUE,
      '#title' => $this->t('Upload a file'),
    ];

    $form['error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom error message.'),
      '#default_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Process the upload and perform validation. Note: we're using the
    // form value for the $replace parameter.
    if (!$form_state->isValueEmpty('file_subdir')) {
      $destination = 'temporary://' . $form_state->getValue('file_subdir');
      \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    }
    else {
      $destination = FALSE;
    }

    // Preset custom error message if requested.
    if ($form_state->getValue('error_message')) {
      $this->messenger->addError($form_state->getValue('error_message'));
    }

    // Setup validators.
    $validators = [];
    if ($form_state->getValue('is_image_file')) {
      $validators['FileIsImage'] = [];
    }

    $allow = $form_state->getValue('allow_all_extensions');
    if ($allow === 'empty_array') {
      $validators['FileExtension'] = [];
    }
    elseif ($allow === 'empty_string') {
      $validators['FileExtension'] = ['extensions' => ''];
    }
    elseif (!$form_state->isValueEmpty('extensions')) {
      $validators['FileExtension'] = ['extensions' => $form_state->getValue('extensions')];
    }

    // The test for \Drupal::service('file_system')->moveUploadedFile()
    // triggering a warning is unavoidable. We're interested in what happens
    // afterwards in _file_save_upload_from_form().
    if ($this->state->get('file_test.disable_error_collection')) {
      define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    }

    $form['file_test_upload']['#upload_validators'] = $validators;
    $form['file_test_upload']['#upload_location'] = $destination;

    $this->messenger->addStatus($this->t('Number of error messages before _file_save_upload_from_form(): @count.', ['@count' => count($this->messenger->messagesByType(MessengerInterface::TYPE_ERROR))]));
    $file = _file_save_upload_from_form($form['file_test_upload'], $form_state, 0, static::fileExistsFromName($form_state->getValue('file_test_replace')));
    $this->messenger->addStatus($this->t('Number of error messages after _file_save_upload_from_form(): @count.', ['@count' => count($this->messenger->messagesByType(MessengerInterface::TYPE_ERROR))]));

    if ($file) {
      $form_state->setValue('file_test_upload', $file);
      $this->messenger->addStatus($this->t('File @filepath was uploaded.', ['@filepath' => $file->getFileUri()]));
      $this->messenger->addStatus($this->t('File name is @filename.', ['@filename' => $file->getFilename()]));
      $this->messenger->addStatus($this->t('File MIME type is @mimetype.', ['@mimetype' => $file->getMimeType()]));
      $this->messenger->addStatus($this->t('You WIN!'));
    }
    elseif ($file === FALSE) {
      $this->messenger->addError($this->t('Epic upload FAIL!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Get a FileExists enum from its name.
   */
  protected static function fileExistsFromName(string $name): FileExists {
    return match ($name) {
      FileExists::Replace->name => FileExists::Replace,
      FileExists::Error->name => FileExists::Error,
      default => FileExists::Rename,
    };
  }

}
