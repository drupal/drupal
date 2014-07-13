<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestCheckboxesZeroForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tests checkboxes zero.
 */
class FormTestCheckboxesZeroForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_checkboxes_zero';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $json = TRUE) {
    $form_state['json'] = $json;
    $form['checkbox_off'] = array(
      '#title' => t('Checkbox off'),
      '#type' => 'checkboxes',
      '#options' => array('foo', 'bar', 'baz'),
    );
    $form['checkbox_zero_default'] = array(
      '#title' => t('Zero default'),
      '#type' => 'checkboxes',
      '#options' => array('foo', 'bar', 'baz'),
      '#default_value' => array(0),
    );
    $form['checkbox_string_zero_default'] = array(
      '#title' => t('Zero default (string)'),
      '#type' => 'checkboxes',
      '#options' => array('foo', 'bar', 'baz'),
      '#default_value' => array('0'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if (!empty($form_state['json'])) {
      $form_state['response'] = new JsonResponse($form_state['values']);
    }
    else {
      $form_state['redirect'] = FALSE;
    }
  }

}
