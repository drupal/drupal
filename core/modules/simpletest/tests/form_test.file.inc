<?php

/**
 * @file
 * An include file to test loading it with the form API.
 */

/**
 * Form constructor for testing FAPI file inclusion of the file specified in
 * hook_menu().
 */
function form_test_load_include_menu($form, &$form_state) {
  // Submit the form via Ajax. That way the FAPI has to care about including
  // the file specified in hook_menu().
  $ajax_wrapper_id = drupal_html_id('form-test-load-include-menu-ajax-wrapper');
  $form['ajax_wrapper'] = array(
    '#markup' => '<div id="' . $ajax_wrapper_id . '"></div>',
  );
  $form['button'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#submit' => array('form_test_load_include_submit'),
    '#ajax' => array(
      'wrapper' => $ajax_wrapper_id,
      'method' => 'append',
      'callback' => 'form_test_load_include_menu_ajax',
    ),
  );
  return $form;
}

/**
 * Submit callback for the form API file inclusion test forms.
 */
function form_test_load_include_submit($form, $form_state) {
  drupal_set_message('Submit callback called.');
}

/**
 * Ajax callback for the file inclusion via menu test.
 */
function form_test_load_include_menu_ajax($form) {
  // We don't need to return anything, since #ajax['method'] is 'append', which
  // does not remove the original #ajax['wrapper'] element, and status messages
  // are automatically added by the Ajax framework as long as there's a wrapper
  // element to add them to.
  return '';
}
