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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_confirm_array_path_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'system.admin',
      'options' => array(
        'query' => array(
          'destination' => 'admin/config',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('ConfirmFormArrayPathTestForm::getCancelText().');
  }

}
