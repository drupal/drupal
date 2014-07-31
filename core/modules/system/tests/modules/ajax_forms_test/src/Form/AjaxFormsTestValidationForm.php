<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestSimpleForm.
 */

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
    $form['drivertext'] = array(
      '#title' => $this->t('AJAX-enabled textfield.'),
      '#description' => $this->t("When this one AJAX-triggers and the spare required field is empty, you should not get an error."),
      '#type' => 'textfield',
      '#default_value' => !empty($form_state['values']['drivertext']) ? $form_state['values']['drivertext'] : "",
      '#ajax' => array(
        'callback' => 'ajax_forms_test_validation_form_callback',
        'wrapper' => 'message_area',
        'method' => 'replace',
      ),
      '#suffix' => '<div id="message_area"></div>',
    );

    $form['drivernumber'] = array(
      '#title' => $this->t('AJAX-enabled number field.'),
      '#description' => $this->t("When this one AJAX-triggers and the spare required field is empty, you should not get an error."),
      '#type' => 'number',
      '#default_value' => !empty($form_state['values']['drivernumber']) ? $form_state['values']['drivernumber'] : "",
      '#ajax' => array(
        'callback' => 'ajax_forms_test_validation_number_form_callback',
        'wrapper' => 'message_area_number',
        'method' => 'replace',
      ),
      '#suffix' => '<div id="message_area_number"></div>',
    );

    $form['spare_required_field'] = array(
      '#title' => $this->t("Spare Required Field"),
      '#type' => 'textfield',
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t("Validation form submitted"));
  }

}
