<?php

namespace Drupal\session_test\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the test config edit forms.
 *
 * @internal
 */
class SessionTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => 'Input',
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(SafeMarkup::format('Ok: @input', ['@input' => $form_state->getValue('input')]));
  }

}
