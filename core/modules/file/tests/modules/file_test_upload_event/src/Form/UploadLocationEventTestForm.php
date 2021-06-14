<?php

namespace Drupal\file_test_upload_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a form for testing the upload location subscriber.
 */
class UploadLocationEventTestForm extends FormBase {

  const UPLOAD_LOCATION_EVENT_TEST_FIDS = 'upload_location_event_test_fid';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_upload_location_event_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      'folder' => [
        '#type' => 'textfield',
        '#title' => new TranslatableMarkup('Folder'),
        '#required' => TRUE,
      ],
      'file' => [
        '#type' => 'managed_file',
        '#title' => new TranslatableMarkup('A file'),
        '#upload_location' => 'public://test',
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => 'submit',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nil-op.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set(self::UPLOAD_LOCATION_EVENT_TEST_FIDS, $form_state->getValue('file'));
  }

}
