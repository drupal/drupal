<?php

declare(strict_types=1);

namespace Drupal\file_test\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * File test form class.
 */
class FileTestForm implements FormInterface {
  use FileTestFormTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_file_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = $this->baseForm($form, $form_state);

    $form['file_test_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload a file'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process the upload and perform validation. Note: we're using the
    // form value for the $replace parameter.
    if (!$form_state->isValueEmpty('file_subdir')) {
      $destination = 'temporary://' . $form_state->getValue('file_subdir');
      \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    }
    else {
      $destination = FALSE;
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
    // afterwards in file_save_upload().
    if (\Drupal::state()->get('file_test.disable_error_collection')) {
      define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    }

    $file = file_save_upload('file_test_upload', $validators, $destination, 0, static::fileExistsFromName($form_state->getValue('file_test_replace')));
    if ($file) {
      $form_state->setValue('file_test_upload', $file);
      \Drupal::messenger()->addStatus($this->t('File @filepath was uploaded.', ['@filepath' => $file->getFileUri()]));
      \Drupal::messenger()->addStatus($this->t('File name is @filename.', ['@filename' => $file->getFilename()]));
      \Drupal::messenger()->addStatus($this->t('File MIME type is @mimetype.', ['@mimetype' => $file->getMimeType()]));
      \Drupal::messenger()->addStatus($this->t('You WIN!'));
    }
    elseif ($file === FALSE) {
      \Drupal::messenger()->addError($this->t('Epic upload FAIL!'));
    }
  }

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
