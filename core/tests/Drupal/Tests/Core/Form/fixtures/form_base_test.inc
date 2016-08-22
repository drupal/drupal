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
  $form['test'] = array(
    '#type' => 'textfield',
    '#title' => 'Test',
  );
  $form['options'] = array(
    '#type' => 'radios',
    '#options' => array(
      'foo' => 'foo',
      'bar' => 'bar',
    ),
  );
  $form['value'] = array(
    '#type' => 'value',
    '#value' => 'bananas',
  );
  $form['actions'] = array(
    '#type' => 'actions',
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit',
  );
  return $form;
}
