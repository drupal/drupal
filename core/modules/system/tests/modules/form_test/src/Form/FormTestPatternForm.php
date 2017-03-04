<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form using the FAPI #pattern property.
 */
class FormTestPatternForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_pattern_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['textfield'] = [
      '#type' => 'textfield',
      '#title' => 'One digit followed by lowercase letters',
      '#pattern' => '[0-9][a-z]+',
    ];
    $form['tel'] = [
      '#type' => 'tel',
      '#title' => 'Everything except numbers',
      '#pattern' => '[^\d]*',
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => 'Password',
      '#pattern' => '[01]+',
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => 'Client side validation',
      '#decription' => 'Just client side validation, using the #pattern attribute.',
      '#attributes' => [
        'pattern' => '.*foo.*',
      ],
      '#pattern' => 'ignored',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
