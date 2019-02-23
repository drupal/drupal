<?php

namespace Drupal\file_test\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * File test form class.
 */
class FileTestForm implements FormInterface {

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
    $form['file_test_upload'] = [
      '#type' => 'file',
      '#title' => t('Upload a file'),
    ];
    $form['file_test_replace'] = [
      '#type' => 'select',
      '#title' => t('Replace existing image'),
      '#options' => [
        FileSystemInterface::EXISTS_RENAME => t('Appends number until name is unique'),
        FileSystemInterface::EXISTS_REPLACE => t('Replace the existing file'),
        FileSystemInterface::EXISTS_ERROR => t('Fail with an error'),
      ],
      '#default_value' => FileSystemInterface::EXISTS_RENAME,
    ];
    $form['file_subdir'] = [
      '#type' => 'textfield',
      '#title' => t('Subdirectory for test file'),
      '#default_value' => '',
    ];

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions.'),
      '#default_value' => '',
    ];

    $form['allow_all_extensions'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow all extensions?'),
      '#default_value' => FALSE,
    ];

    $form['is_image_file'] = [
      '#type' => 'checkbox',
      '#title' => t('Is this an image file?'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
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
      $validators['file_validate_is_image'] = [];
    }

    if ($form_state->getValue('allow_all_extensions')) {
      $validators['file_validate_extensions'] = [];
    }
    elseif (!$form_state->isValueEmpty('extensions')) {
      $validators['file_validate_extensions'] = [$form_state->getValue('extensions')];
    }

    // The test for \Drupal::service('file_system')->moveUploadedFile()
    // triggering a warning is unavoidable. We're interested in what happens
    // afterwards in file_save_upload().
    if (\Drupal::state()->get('file_test.disable_error_collection')) {
      define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    }

    $file = file_save_upload('file_test_upload', $validators, $destination, 0, $form_state->getValue('file_test_replace'));
    if ($file) {
      $form_state->setValue('file_test_upload', $file);
      \Drupal::messenger()->addStatus(t('File @filepath was uploaded.', ['@filepath' => $file->getFileUri()]));
      \Drupal::messenger()->addStatus(t('File name is @filename.', ['@filename' => $file->getFilename()]));
      \Drupal::messenger()->addStatus(t('File MIME type is @mimetype.', ['@mimetype' => $file->getMimeType()]));
      \Drupal::messenger()->addStatus(t('You WIN!'));
    }
    elseif ($file === FALSE) {
      \Drupal::messenger()->addError(t('Epic upload FAIL!'));
    }
  }

}
