<?php

namespace Drupal\batch_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generate form of id batch_test_mock_form.
 *
 * @internal
 */
class BatchTestMockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_test_mock_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test_value'] = [
      '#title' => t('Test value'),
      '#type' => 'textfield',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    batch_test_stack('mock form submitted with value = ' . $form_state->getValue('test_value'));
  }

}
