<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder to test programmatic form submissions.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['textfield'] = [
      '#title' => 'Textfield',
      '#type' => 'textfield',
    ];

    $form['checkboxes'] = [
      '#title' => t('Checkboxes'),
      '#type' => 'checkboxes',
      '#options' => [
        1 => 'First checkbox',
        2 => 'Second checkbox',
      ],
      // Both checkboxes are selected by default so that we can test the ability
      // of programmatic form submissions to uncheck them.
      '#default_value' => [1, 2],
    ];

    $form['field_to_validate'] = [
      '#type' => 'radios',
      '#title' => 'Field to validate (in the case of limited validation)',
      '#description' => 'If the form is submitted by clicking the "Submit with limited validation" button, then validation can be limited based on the value of this radio button.',
      '#options' => [
        'all' => 'Validate all fields',
        'textfield' => 'Validate the "Textfield" field',
        'field_to_validate' => 'Validate the "Field to validate" field',
      ],
      '#default_value' => 'all',
    ];

    $form['field_restricted'] = [
      '#type' => 'textfield',
      '#title' => 'Textfield (no access)',
      '#access' => FALSE,
    ];

    // The main submit button for the form.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    // A secondary submit button that allows validation to be limited based on
    // the value of the above radio selector.
    $form['submit_limit_validation'] = [
      '#type' => 'submit',
      '#value' => 'Submit with limited validation',
      // Use the same submit handler for this button as for the form itself.
      // (This must be set explicitly or otherwise the form API will ignore the
      // #limit_validation_errors property.)
      '#submit' => ['::submitForm'],
    ];
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['field_to_validate']) && $user_input['field_to_validate'] != 'all') {
      $form['submit_limit_validation']['#limit_validation_errors'] = [
        [$user_input['field_to_validate']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('textfield')) {
      $form_state->setErrorByName('textfield', t('Textfield is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('programmatic_form_submit', $form_state->getValues());
  }

}
