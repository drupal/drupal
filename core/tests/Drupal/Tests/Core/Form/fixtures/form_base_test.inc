<?php

/**
 * @file
 * Functions in the global namespace for \Drupal\Tests\Core\Form\FormTestBase.
 */

/**
 * Creates a test form.
 *
 * @return array
 *   The form array
 */
function test_form_id() {
  $form['test'] = [
    '#type' => 'textfield',
    '#title' => 'Test',
  ];
  $form['options'] = [
    '#type' => 'radios',
    '#options' => [
      'foo' => 'foo',
      'bar' => 'bar',
    ],
  ];
  $form['value'] = [
    '#type' => 'value',
    '#value' => 'bananas',
  ];
  $form['actions'] = [
    '#type' => 'actions',
  ];
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit',
  ];
  return $form;
}
