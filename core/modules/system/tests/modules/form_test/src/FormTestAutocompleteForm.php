<?php

namespace Drupal\form_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a test form using autocomplete textfields.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['autocomplete_1'] = [
      '#type' => 'textfield',
      '#title' => 'Autocomplete 1',
      '#autocomplete_route_name' => 'form_test.autocomplete_1',
    ];
    $form['autocomplete_2'] = [
      '#type' => 'textfield',
      '#title' => 'Autocomplete 2',
      '#autocomplete_route_name' => 'form_test.autocomplete_2',
      '#autocomplete_route_parameters' => ['param' => 'value'],
    ];
    $form['autocomplete_3'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],
      'autocomplete_3' => [
        '#type' => 'textfield',
        '#title' => 'Autocomplete 3',
        '#autocomplete_route_name' => 'form_test.autocomplete_1',
      ],
    ];
    $form['autocomplete_4'] = [
      '#type' => 'textfield',
      '#title' => 'Autocomplete 4',
      '#autocomplete_route_name' => 'form_test.autocomplete_1',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
