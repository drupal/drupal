<?php

namespace Drupal\form_test;

use Drupal\Core\Url;

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
  public function getCancelUrl() {
    return new Url('form_test.route6', [], [
      'query' => [
        'destination' => 'admin/config',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('ConfirmFormArrayPathTestForm::getCancelText().');
  }

}
