<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test the placeholder attribute.
 *
 * @internal
 */
class FormTestPlaceholderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_placeholder_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach (['textfield', 'textarea', 'url', 'password', 'search', 'tel', 'email', 'number'] as $type) {
      $form[$type] = [
        '#type' => $type,
        '#title' => $type,
        '#placeholder' => 'placeholder-text',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
