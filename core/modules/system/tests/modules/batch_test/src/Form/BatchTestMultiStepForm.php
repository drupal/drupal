<?php

/**
 * @file
 * Contains \Drupal\batch_test\Form\BatchTestMultiStepForm.
 */

namespace Drupal\batch_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generate form of id batch_test_multistep_form.
 */
class BatchTestMultiStepForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_test_multistep_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($form_state['storage']['step'])) {
      $form_state['storage']['step'] = 1;
    }

    $form['step_display'] = array(
      '#markup' => 'step ' . $form_state['storage']['step'] . '<br/>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_test_stack(NULL, TRUE);

    switch ($form_state['storage']['step']) {
      case 1:
        batch_set(_batch_test_batch_1());
        break;
      case 2:
        batch_set(_batch_test_batch_2());
        break;
    }

    if ($form_state['storage']['step'] < 2) {
      $form_state['storage']['step']++;
      $form_state['rebuild'] = TRUE;
    }

    $form_state->setRedirect('batch_test.redirect');
  }

}
