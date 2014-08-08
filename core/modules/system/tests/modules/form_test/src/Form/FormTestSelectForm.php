<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestSelectForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a form to test #type 'select' validation.
 */
class FormTestSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base = array(
      '#type' => 'select',
      '#options' => array('one' => 'one', 'two' => 'two', 'three' => 'three'),
    );

    $form['select'] = $base + array(
      '#title' => '#default_value one',
      '#default_value' => 'one',
    );
    $form['select_required'] = $base + array(
      '#title' => '#default_value one, #required',
      '#required' => TRUE,
      '#default_value' => 'one',
    );
    $form['select_optional'] = $base + array(
      '#title' => '#default_value one, not #required',
      '#required' => FALSE,
      '#default_value' => 'one',
    );
    $form['empty_value'] = $base + array(
      '#title' => '#default_value one, #required, #empty_value 0',
      '#required' => TRUE,
      '#default_value' => 'one',
      '#empty_value' => 0,
    );
    $form['empty_value_one'] = $base + array(
      '#title' => '#default_value = #empty_value, #required',
      '#required' => TRUE,
      '#default_value' => 'one',
      '#empty_value' => 'one',
    );

    $form['no_default'] = $base + array(
      '#title' => 'No #default_value, #required',
      '#required' => TRUE,
    );
    $form['no_default_optional'] = $base + array(
      '#title' => 'No #default_value, not #required',
      '#required' => FALSE,
      '#description' => 'Should result in "one" because it is not required and there is no #empty_value requested, so default browser behavior of preselecting first option is in effect.',
    );
    $form['no_default_optional_empty_value'] = $base + array(
      '#title' => 'No #default_value, not #required, #empty_value empty string',
      '#empty_value' => '',
      '#required' => FALSE,
      '#description' => 'Should result in an empty string (due to #empty_value) because it is optional.',
    );

    $form['no_default_empty_option'] = $base + array(
      '#title' => 'No #default_value, #required, #empty_option',
      '#required' => TRUE,
      '#empty_option' => '- Choose -',
    );
    $form['no_default_empty_option_optional'] = $base + array(
      '#title' => 'No #default_value, not #required, #empty_option',
      '#empty_option' => '- Dismiss -',
      '#description' => 'Should result in an empty string (default of #empty_value) because it is optional.',
    );

    $form['no_default_empty_value'] = $base + array(
      '#title' => 'No #default_value, #required, #empty_value 0',
      '#required' => TRUE,
      '#empty_value' => 0,
      '#description' => 'Should never result in 0.',
    );
    $form['no_default_empty_value_one'] = $base + array(
      '#title' => 'No #default_value, #required, #empty_value one',
      '#required' => TRUE,
      '#empty_value' => 'one',
      '#description' => 'A mistakenly assigned #empty_value contained in #options should not be valid.',
    );
    $form['no_default_empty_value_optional'] = $base + array(
      '#title' => 'No #default_value, not #required, #empty_value 0',
      '#required' => FALSE,
      '#empty_value' => 0,
      '#description' => 'Should result in 0 because it is optional.',
    );

    $form['multiple'] = $base + array(
      '#title' => '#multiple, #default_value two',
      '#default_value' => array('two'),
      '#multiple' => TRUE,
    );
    $form['multiple_no_default'] = $base + array(
      '#title' => '#multiple, no #default_value',
      '#multiple' => TRUE,
    );
    $form['multiple_no_default_required'] = $base + array(
      '#title' => '#multiple, #required, no #default_value',
      '#required' => TRUE,
      '#multiple' => TRUE,
    );

    $form['submit'] = array('#type' => 'submit', '#value' => 'Submit');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
