<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestValidateRequiredNoTitleForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form constructor to test the #required property without #title.
 */
class FormTestValidateRequiredNoTitleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_validate_required_form_no_title';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['textfield'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => 'Submit');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message('The form_test_validate_required_form_no_title form was submitted successfully.');
  }

}
