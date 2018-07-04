<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to test whether machine name validation works with ajax requests.
 */
class FormTestMachineNameValidationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_machine_name_validation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Disable client-side validation so that we can test AJAX requests with
    // invalid input.
    $form['#attributes']['novalidate'] = 'novalidate';

    $form['name'] = [
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('name'),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#title' => 'Name',
    ];

    // The default value simulates how an entity form works, which has default
    // values based on an entity, which is updated in an afterBuild callback.
    // During validation and after build, limit_validation_errors is not
    // in effect, which means that getValue('id') does return a value, while it
    // does not during the submit callback. Therefore, this test sets the value
    // in ::buildAjaxSnackConfigureFormValidate() and then uses that as the
    // default value, so that the default value and the value are identical.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $form_state->get('id'),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'load'],
        'source' => ['name'],
      ],
    ];

    // Test support for multiple machine names on the form. Although this has
    // the default value duplicate it should not generate an error because it
    // is the default value.
    $form['id2'] = [
      '#type' => 'machine_name',
      '#default_value' => 'duplicate',
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'load'],
        'source' => ['name'],
      ],
    ];

    $form['snack'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a snack'),
      '#options' => [
        'apple' => 'apple',
        'pear' => 'pear',
        'potato' => 'potato',
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::buildAjaxSnackConfigureForm',
        'wrapper' => 'snack-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $form['snack_configs'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'snack-config-form',
      ],
      '#tree' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * Validate callback that forces a form rebuild.
   */
  public function buildAjaxSnackConfigureFormValidate(array $form, FormStateInterface $form_state) {
    $form_state->set('id', $form_state->getValue('id'));
  }

  /**
   * Submit callback that forces a form rebuild.
   */
  public function buildAjaxSnackConfigureFormSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Handles changes to the selected snack configuration.
   */
  public function buildAjaxSnackConfigureForm(array $form, FormStateInterface $form_state) {
    return $form['snack_configs'];
  }

  /**
   * Loading stub for machine name
   *
   * @param $machine_name
   * @return bool
   */
  public function load($machine_name) {
    if (strpos($machine_name, 'duplicate') !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus('The form_test_machine_name_validation_form form has been submitted successfully.');
  }

}
