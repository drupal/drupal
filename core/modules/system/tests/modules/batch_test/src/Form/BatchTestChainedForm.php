<?php

/**
 * @file
 * Contains \Drupal\batch_test\Form\BatchTestChainedForm.
 */

namespace Drupal\batch_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Generate form of id batch_test_chained_form.
 */
class BatchTestChainedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_test_chained_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // This value is used to test that $form_state persists through batched
    // submit handlers.
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => 1,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    $form['#submit'] = array(
      'Drupal\batch_test\Form\BatchTestChainedForm::batchTestChainedFormSubmit1',
      'Drupal\batch_test\Form\BatchTestChainedForm::batchTestChainedFormSubmit2',
      'Drupal\batch_test\Form\BatchTestChainedForm::batchTestChainedFormSubmit3',
      'Drupal\batch_test\Form\BatchTestChainedForm::batchTestChainedFormSubmit4',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

  /**
   * Form submission handler #1 for batch_test_chained_form
   */
  public static function batchTestChainedFormSubmit1($form, &$form_state) {
    batch_test_stack(NULL, TRUE);

    batch_test_stack('submit handler 1');
    batch_test_stack('value = ' . $form_state['values']['value']);

    $form_state['values']['value']++;
    batch_set(_batch_test_batch_1());

    $form_state['redirect_route'] = new Url('batch_test.redirect');
  }

  /**
   * Form submission handler #2 for batch_test_chained_form
   */
  public static function batchTestChainedFormSubmit2($form, &$form_state) {
    batch_test_stack('submit handler 2');
    batch_test_stack('value = ' . $form_state['values']['value']);

    $form_state['values']['value']++;
    batch_set(_batch_test_batch_2());

    $form_state['redirect_route'] = new Url('batch_test.redirect');
  }

  /**
   * Form submission handler #3 for batch_test_chained_form
   */
  public static function batchTestChainedFormSubmit3($form, &$form_state) {
    batch_test_stack('submit handler 3');
    batch_test_stack('value = ' . $form_state['values']['value']);

    $form_state['values']['value']++;

    $form_state['redirect_route'] = new Url('batch_test.redirect');
  }

  /**
   * Form submission handler #4 for batch_test_chained_form
   */
  public static function batchTestChainedFormSubmit4($form, &$form_state) {
    batch_test_stack('submit handler 4');
    batch_test_stack('value = ' . $form_state['values']['value']);

    $form_state['values']['value']++;
    batch_set(_batch_test_batch_3());

    $form_state['redirect_route'] = new Url('batch_test.redirect');
  }

}
