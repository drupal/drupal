<?php

/**
 * @file
 * Contains \Drupal\ajax_forms_test\Form\AjaxFormsTestSimpleForm.
 */

namespace Drupal\ajax_forms_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\ajax_forms_test\Callbacks;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder: Builds a form that triggers a simple AJAX callback.
 */
class AjaxFormsTestSimpleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_forms_test_simple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $object = new Callbacks();

    $form = array();
    $form['select'] = array(
      '#title' => $this->t('Color'),
      '#type' => 'select',
      '#options' => array(
        'red' => 'red',
        'green' => 'green',
        'blue' => 'blue'),
      '#ajax' => array(
        'callback' => array($object, 'selectCallback'),
      ),
      '#suffix' => '<div id="ajax_selected_color">No color yet selected</div>',
    );

    $form['checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Test checkbox'),
      '#ajax' => array(
        'callback' => array($object, 'checkboxCallback'),
      ),
      '#suffix' => '<div id="ajax_checkbox_value">No action yet</div>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('submit'),
    );

    // This is for testing invalid callbacks that should return a 500 error in
    // \Drupal\Core\Form\FormAjaxResponseBuilderInterface::buildResponse().
    $invalid_callbacks = array(
      'null' => NULL,
      'empty' => '',
      'nonexistent' => 'some_function_that_does_not_exist',
    );
    foreach ($invalid_callbacks as $key => $value) {
      $form['select_' . $key . '_callback'] = array(
        '#type' => 'select',
        '#title' => $this->t('Test %key callbacks', array('%key' => $key)),
        '#options' => array('red' => 'red'),
        '#ajax' => array('callback' => $value),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
