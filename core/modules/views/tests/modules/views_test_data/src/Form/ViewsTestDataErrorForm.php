<?php

namespace Drupal\views_test_data\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a test form that has a validation error.
 */
class ViewsTestDataErrorForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_test_data_error_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setErrorByName('text', t('Form validation error'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
