<?php

/**
 * @file
 * Theme setting callbacks for the test_theme theme.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 */
function test_theme_form_system_theme_settings_alter(&$form, &$form_state) {
  $form['test_theme_checkbox'] = array(
    '#type' => 'checkbox',
    '#title' => 'Test theme checkbox',
    '#default_value' => theme_get_setting('test_theme_checkbox'),
  );

  // Force the form to be cached so we can test that this file is properly
  // loaded and the custom submit handler is properly called even on a cached
  // form build.
  $form_state['cache'] = TRUE;
  $form['#submit'][] = 'test_theme_form_system_theme_settings_submit';
}

/**
 * Form submission handler for the test theme settings form.
 *
 * @see test_theme_form_system_theme_settings_alter()
 */
function test_theme_form_system_theme_settings_submit($form, &$form_state) {
  drupal_set_message('The test theme setting was saved.');
}
