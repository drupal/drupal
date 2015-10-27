<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestDetailsForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #group property on #type 'container'.
 */
class FormTestDetailsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['meta'] = [
      '#type' => 'details',
      '#title' => 'Details element',
      '#open' => TRUE,
    ];
    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setErrorByName('meta', 'I am an error on the details element.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
