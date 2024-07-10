<?php

declare(strict_types=1);

namespace Drupal\router_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to test _form routing.
 *
 * @internal
 */
class Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'router_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus('The router_test_form form has been submitted successfully.');
  }

}
