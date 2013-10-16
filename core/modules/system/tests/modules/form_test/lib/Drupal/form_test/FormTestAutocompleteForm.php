<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestAutocompleteForm.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormBase;

/**
 * Defines a test form using autocomplete textfields.
 */
class FormTestAutocompleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['autocomplete_1'] = array(
      '#type' => 'textfield',
      '#title' => 'Autocomplete 1',
      '#autocomplete_route_name' => 'form_test.autocomplete_1',
    );
    $form['autocomplete_2'] = array(
      '#type' => 'textfield',
      '#title' => 'Autocomplete 2',
      '#autocomplete_route_name' => 'form_test.autocomplete_2',
      '#autocomplete_route_parameters' => array('param' => 'value'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
