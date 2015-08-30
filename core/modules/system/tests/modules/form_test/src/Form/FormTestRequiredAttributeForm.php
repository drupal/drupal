<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestRequiredAttributeForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test the required attribute.
 */
class FormTestRequiredAttributeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_required_attribute';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach (array('textfield', 'textarea', 'password') as $type) {
      $form[$type] = array(
        '#type' => $type,
        '#required' => TRUE,
        '#title' => $type,
      );
    }
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
