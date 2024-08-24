<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test form button classes.
 *
 * @internal
 */
class FormTestButtonClassForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_button_class';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['button'] = [
      '#type' => 'button',
      '#value' => 'test',
      '#button_type' => 'foo',
    ];
    $form['delete'] = [
      '#type' => 'button',
      '#value' => 'Delete',
      '#button_type' => 'danger',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
