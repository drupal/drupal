<?php

namespace Drupal\batch_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generate form of id batch_test_simple_form.
 *
 * @internal
 */
class BatchTestSimpleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_test_simple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['batch'] = [
      '#type' => 'select',
      '#title' => 'Choose batch',
      '#options' => [
        'batch_0' => 'batch 0',
        'batch_1' => 'batch 1',
        'batch_2' => 'batch 2',
        'batch_3' => 'batch 3',
        'batch_4' => 'batch 4',
        'batch_6' => 'batch 6',
        'batch_7' => 'batch 7',
      ],
      '#multiple' => TRUE,
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
    batch_test_stack(NULL, TRUE);

    foreach ($form_state->getValue('batch') as $batch) {
      $function = '_batch_test_' . $batch;
      batch_set($function());
    }

    $form_state->setRedirect('batch_test.redirect');
  }

}
