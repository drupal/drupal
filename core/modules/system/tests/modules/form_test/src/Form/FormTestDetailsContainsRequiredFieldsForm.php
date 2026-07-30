<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test required fields within details elements.
 *
 * @internal
 */
class FormTestDetailsContainsRequiredFieldsForm extends FormBase {

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
    $form['meta2'] = [
      '#type' => 'details',
      '#title' => 'Details element 2',
      '#open' => FALSE,
    ];
    $form['meta2']['required_textarea_in_details'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => 'Required textarea',
    ];
    $form['meta3'] = [
      '#type' => 'details',
      '#title' => 'Details element 3',
      '#open' => FALSE,
    ];
    $form['meta3']['required_select_in_details'] = [
      '#type' => 'select',
      '#options' => ['one', 'two', 'three', 'four', 'five'],
      '#required' => TRUE,
      '#title' => 'Required select',
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
  public function submitForm(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

}
