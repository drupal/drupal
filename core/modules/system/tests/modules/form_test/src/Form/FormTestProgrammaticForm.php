<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestProgrammaticForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form builder to test programmatic form submissions.
 */
class FormTestProgrammaticForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_programmatic_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['textfield'] = array(
      '#title' => 'Textfield',
      '#type' => 'textfield',
    );

    $form['checkboxes'] = array(
      '#title' => t('Checkboxes'),
      '#type' => 'checkboxes',
      '#options' => array(
        1 => 'First checkbox',
        2 => 'Second checkbox',
      ),
      // Both checkboxes are selected by default so that we can test the ability
      // of programmatic form submissions to uncheck them.
      '#default_value' => array(1, 2),
    );

    $form['field_to_validate'] = array(
      '#type' => 'radios',
      '#title' => 'Field to validate (in the case of limited validation)',
      '#description' => 'If the form is submitted by clicking the "Submit with limited validation" button, then validation can be limited based on the value of this radio button.',
      '#options' => array(
        'all' => 'Validate all fields',
        'textfield' => 'Validate the "Textfield" field',
        'field_to_validate' => 'Validate the "Field to validate" field',
      ),
      '#default_value' => 'all',
    );

    $form['field_restricted'] = array(
      '#type' => 'textfield',
      '#title' => 'Textfield (no access)',
      '#access' => FALSE,
    );

    // The main submit button for the form.
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    // A secondary submit button that allows validation to be limited based on
    // the value of the above radio selector.
    $form['submit_limit_validation'] = array(
      '#type' => 'submit',
      '#value' => 'Submit with limited validation',
      // Use the same submit handler for this button as for the form itself.
      // (This must be set explicitly or otherwise the form API will ignore the
      // #limit_validation_errors property.)
      '#submit' => array(array($this, 'submitForm')),
    );
    if (!empty($form_state['input']['field_to_validate']) && $form_state['input']['field_to_validate'] != 'all') {
      $form['submit_limit_validation']['#limit_validation_errors'] = array(
        array($form_state['input']['field_to_validate']),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (empty($form_state['values']['textfield'])) {
      form_set_error('textfield', $form_state, t('Textfield is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['storage']['programmatic_form_submit'] = $form_state['values'];
  }

}
