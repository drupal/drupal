<?php

/**
 * @file
 * Contains \Drupal\form_test\ConfirmFormArrayPathTestForm.
 */

namespace Drupal\form_test;

/**
 * Provides a test confirmation form with a complex cancellation destination.
 */
class ConfirmFormArrayPathTestForm extends ConfirmFormTestForm {

  /**
   * Overrides \Drupal\form_test\ConfirmFormTestForm::getFormID().
   */
  public function getFormID() {
    return 'form_test_confirm_array_path_test_form';
  }

  /**
   * Overrides \Drupal\form_test\ConfirmFormTestForm::getCancelPath().
   */
  public function getCancelPath() {
    return array(
      'path' => 'admin',
      'query' => array(
        'destination' => 'admin/config',
      ),
    );
  }

  /**
   * Overrides \Drupal\form_test\ConfirmFormTestForm::getCancelText().
   */
  public function getCancelText() {
    return t('ConfirmFormArrayPathTestForm::getCancelText().');
  }

}
