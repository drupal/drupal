<?php

declare(strict_types=1);

namespace Drupal\batch_test\Form;

use Drupal\batch_test\BatchTestDefinitions;
use Drupal\batch_test\BatchTestHelper;
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
        'batch0' => 'batch 0',
        'batch1' => 'batch 1',
        'batch2' => 'batch 2',
        'batch3' => 'batch 3',
        'batch4' => 'batch 4',
        'batch6' => 'batch 6',
        'batch7' => 'batch 7',
        'batch8' => 'batch 8',
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
    $batch_test_definitions = new BatchTestDefinitions();
    $batch_test_helper = new BatchTestHelper();
    $batch_test_helper->stack(NULL, TRUE);

    foreach ($form_state->getValue('batch') as $batch) {
      batch_set($batch_test_definitions->$batch());
    }

    $form_state->setRedirect('batch_test.redirect');
  }

}
