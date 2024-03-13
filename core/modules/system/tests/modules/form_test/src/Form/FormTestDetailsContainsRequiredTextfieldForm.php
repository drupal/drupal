<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test a required textfield within a details element.
 *
 * @internal
 */
class FormTestDetailsContainsRequiredTextfieldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'form_test_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $use_ajax = FALSE): array {

    $form['meta'] = [
      '#type' => 'details',
      '#title' => 'Details element',
      '#open' => FALSE,
    ];
    $form['meta']['required_textfield_in_details'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => 'Required textfield',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    if ($use_ajax) {
      $form['submitAjax'] = [
        '#type' => 'submit',
        '#value' => 'Submit Ajax',
        '#ajax' => [
          'callback' => '::submitForm',
          'event' => 'click',
          'wrapper' => 'form-test-details-form',
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
