<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestRangeInvalidForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for testing invalid #type 'range' elements.
 */
class FormTestRangeInvalidForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_range_invalid';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['minmax'] = array(
      '#type' => 'range',
      '#min' => 10,
      '#max' => 5,
      '#title' => 'Invalid range',
      '#description' => 'Minimum greater than maximum.',
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
  }

}
