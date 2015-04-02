<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestPatternForm.
 */

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
    $form['textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'One digit followed by lowercase letters',
      '#pattern' => '[0-9][a-z]+',
    );
    $form['tel'] = array(
      '#type' => 'tel',
      '#title' => 'Everything except numbers',
      '#pattern' => '[^\d]*',
    );
    $form['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#pattern' => '[01]+',
    );
    $form['url'] = array(
      '#type' => 'url',
      '#title' => 'Client side validation',
      '#decription' => 'Just client side validation, using the #pattern attribute.',
      '#attributes' => array(
        'pattern' => '.*foo.*',
      ),
      '#pattern' => 'ignored',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
