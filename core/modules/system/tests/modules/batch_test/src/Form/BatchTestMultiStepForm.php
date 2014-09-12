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
    $step = $form_state->get('step');
    if (empty($step)) {
      $step = 1;
      $form_state->set('step', $step);
    }

    $form['step_display'] = array(
      '#markup' => 'step ' . $step . '<br/>',
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

    $step = $form_state->get('step');
    switch ($step) {
      case 1:
        batch_set(_batch_test_batch_1());
        break;
      case 2:
        batch_set(_batch_test_batch_2());
        break;
    }

    if ($step < 2) {
      $form_state->set('step', ++$step);
      $form_state->setRebuild();
    }

    $form_state->setRedirect('batch_test.redirect');
  }

}
