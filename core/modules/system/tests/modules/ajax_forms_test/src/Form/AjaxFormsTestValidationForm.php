<?php

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that triggers a simple AJAX callback.
 */
class AjaxFormsTestValidationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_forms_test_validation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['drivertext'] = [
      '#title' => $this->t('AJAX-enabled textfield.'),
      '#description' => $this->t("When this one AJAX-triggers and the spare required field is empty, you should not get an error."),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('drivertext', ''),
      '#ajax' => [
        'callback' => 'ajax_forms_test_validation_form_callback',
        'wrapper' => 'message_area',
        'method' => 'replace',
      ],
      '#suffix' => '<div id="message_area"></div>',
    ];

    $form['drivernumber'] = [
      '#title' => $this->t('AJAX-enabled number field.'),
      '#description' => $this->t("When this one AJAX-triggers and the spare required field is empty, you should not get an error."),
      '#type' => 'number',
      '#default_value' => $form_state->getValue('drivernumber', ''),
      '#ajax' => [
        'callback' => 'ajax_forms_test_validation_number_form_callback',
        'wrapper' => 'message_area_number',
        'method' => 'replace',
      ],
      '#suffix' => '<div id="message_area_number"></div>',
    ];

    $form['spare_required_field'] = [
      '#title' => $this->t("Spare Required Field"),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t("Validation form submitted"));
  }

}
