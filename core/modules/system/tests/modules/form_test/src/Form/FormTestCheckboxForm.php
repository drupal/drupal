<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FormTestCheckboxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_test_checkbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // A required checkbox.
    $form['required_checkbox'] = array(
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => 'required_checkbox',
    );

    // A disabled checkbox should get its default value back.
    $form['disabled_checkbox_on'] = array(
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#return_value' => 'disabled_checkbox_on',
      '#default_value' => 'disabled_checkbox_on',
      '#title' => 'disabled_checkbox_on',
    );
    $form['disabled_checkbox_off'] = array(
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#return_value' => 'disabled_checkbox_off',
      '#default_value' => NULL,
      '#title' => 'disabled_checkbox_off',
    );

    // A checkbox is active when #default_value == #return_value.
    $form['checkbox_on'] = array(
      '#type' => 'checkbox',
      '#return_value' => 'checkbox_on',
      '#default_value' => 'checkbox_on',
      '#title' => 'checkbox_on',
    );

    // But inactive in any other case.
    $form['checkbox_off'] = array(
      '#type' => 'checkbox',
      '#return_value' => 'checkbox_off',
      '#default_value' => 'checkbox_on',
      '#title' => 'checkbox_off',
    );

    // Checkboxes with a #return_value of '0' are supported.
    $form['zero_checkbox_on'] = array(
      '#type' => 'checkbox',
      '#return_value' => '0',
      '#default_value' => '0',
      '#title' => 'zero_checkbox_on',
    );

    // In that case, passing a #default_value != '0'
    // means that the checkbox is off.
    $form['zero_checkbox_off'] = array(
      '#type' => 'checkbox',
      '#return_value' => '0',
      '#default_value' => '1',
      '#title' => 'zero_checkbox_off',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
