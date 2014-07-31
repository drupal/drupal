<?php
/**
 * @file
 * Contains \Drupal\file_test\Form\FileTestForm.
 */

namespace Drupal\file_test\Form;

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
    $form['file_test_upload'] = array(
      '#type' => 'file',
      '#title' => t('Upload a file'),
    );
    $form['file_test_replace'] = array(
      '#type' => 'select',
      '#title' => t('Replace existing image'),
      '#options' => array(
        FILE_EXISTS_RENAME => t('Appends number until name is unique'),
        FILE_EXISTS_REPLACE => t('Replace the existing file'),
        FILE_EXISTS_ERROR => t('Fail with an error'),
      ),
      '#default_value' => FILE_EXISTS_RENAME,
    );
    $form['file_subdir'] = array(
      '#type' => 'textfield',
      '#title' => t('Subdirectory for test file'),
      '#default_value' => '',
    );

    $form['extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed extensions.'),
      '#default_value' => '',
    );

    $form['allow_all_extensions'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow all extensions?'),
      '#default_value' => FALSE,
    );

    $form['is_image_file'] = array(
      '#type' => 'checkbox',
      '#title' => t('Is this an image file?'),
      '#default_value' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
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
    if (!empty($form_state['values']['file_subdir'])) {
      $destination = 'temporary://' . $form_state['values']['file_subdir'];
      file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    }
    else {
      $destination = FALSE;
    }

    // Setup validators.
    $validators = array();
    if ($form_state['values']['is_image_file']) {
      $validators['file_validate_is_image'] = array();
    }

    if ($form_state['values']['allow_all_extensions']) {
      $validators['file_validate_extensions'] = array();
    }
    elseif (!empty($form_state['values']['extensions'])) {
      $validators['file_validate_extensions'] = array($form_state['values']['extensions']);
    }

    $file = file_save_upload('file_test_upload', $validators, $destination, 0, $form_state['values']['file_test_replace']);
    if ($file) {
      $form_state['values']['file_test_upload'] = $file;
      drupal_set_message(t('File @filepath was uploaded.', array('@filepath' => $file->getFileUri())));
      drupal_set_message(t('File name is @filename.', array('@filename' => $file->getFilename())));
      drupal_set_message(t('File MIME type is @mimetype.', array('@mimetype' => $file->getMimeType())));
      drupal_set_message(t('You WIN!'));
    }
    elseif ($file === FALSE) {
      drupal_set_message(t('Epic upload FAIL!'), 'error');
    }
  }
}
