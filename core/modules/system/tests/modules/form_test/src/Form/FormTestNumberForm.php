<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test #type 'number' and 'range' validation.
 */
class FormTestNumberForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_number';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $element = 'number') {
    $base = [
      '#type' => $element,
    ];

    $form['integer_no_number'] = $base + [
      '#title' => 'Integer test, #no_error',
      '#default_value' => '#no_number',
    ];
    $form['integer_no_step'] = $base + [
      '#title' => 'Integer test without step',
      '#default_value' => 5,
    ];
    $form['integer_no_step_step_error'] = $base + [
      '#title' => 'Integer test without step, #step_error',
      '#default_value' => 4.5,
    ];
    $form['integer_step'] = $base + [
      '#title' => 'Integer test with step',
      '#default_value' => 5,
      '#step' => 1,
    ];
    $form['integer_step_error'] = $base + [
      '#title' => 'Integer test, with step, #step_error',
      '#default_value' => 5,
      '#step' => 2,
    ];
    $form['integer_step_min'] = $base + [
      '#title' => 'Integer test with min value',
      '#default_value' => 5,
      '#min' => 0,
      '#step' => 1,
    ];
    $form['integer_step_min_error'] = $base + [
      '#title' => 'Integer test with min value, #min_error',
      '#default_value' => 5,
      '#min' => 6,
      '#step' => 1,
    ];
    $form['integer_step_max'] = $base + [
      '#title' => 'Integer test with max value',
      '#default_value' => 5,
      '#max' => 6,
      '#step' => 1,
    ];
    $form['integer_step_max_error'] = $base + [
      '#title' => 'Integer test with max value, #max_error',
      '#default_value' => 5,
      '#max' => 4,
      '#step' => 1,
    ];
    $form['integer_step_min_border'] = $base + [
      '#title' => 'Integer test with min border check',
      '#default_value' => -1,
      '#min' => -1,
      '#step' => 1,
    ];
    $form['integer_step_max_border'] = $base + [
      '#title' => 'Integer test with max border check',
      '#default_value' => 5,
      '#max' => 5,
      '#step' => 1,
    ];
    $form['integer_step_based_on_min'] = $base + [
      '#title' => 'Integer test with step based on min check',
      '#default_value' => 3,
      '#min' => -1,
      '#step' => 2,
    ];
    $form['integer_step_based_on_min_error'] = $base + [
      '#title' => 'Integer test with step based on min check, #step_error',
      '#default_value' => 4,
      '#min' => -1,
      '#step' => 2,
    ];
    $form['float_small_step'] = $base + [
      '#title' => 'Float test with a small step',
      '#default_value' => 4,
      '#step' => 0.0000000000001,
    ];
    $form['float_step_no_error'] = $base + [
      '#title' => 'Float test',
      '#default_value' => 1.2,
      '#step' => 0.3,
    ];
    $form['float_step_error'] = $base + [
      '#title' => 'Float test, #step_error',
      '#default_value' => 1.3,
      '#step' => 0.3,
    ];
    $form['float_step_hard_no_error'] = $base + [
      '#title' => 'Float test hard',
      '#default_value' => 0.9411764729088,
      '#step' => 0.00392156863712,
    ];
    $form['float_step_hard_error'] = $base + [
      '#title' => 'Float test hard, #step_error',
      '#default_value' => 0.9411764,
      '#step' => 0.00392156863,
    ];
    $form['float_step_any_no_error'] = $base + [
      '#title' => 'Arbitrary float',
      '#default_value' => 0.839562930284,
      '#step' => 'aNy',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
