<?php
// $Id: form_test.file.inc,v 1.1 2010/07/17 18:52:39 dries Exp $

/**
 * @file
 * An include file to test loading it with the form API.
 */

/**
 * Form constructor for testing FAPI file inclusion of the file specified in
 * hook_menu().
 */
function form_test_load_include_menu($form, &$form_state) {
  // Submit the form via AJAX. That way the FAPI has to care about including
  // the file specified in hook_menu().
  $form['button'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#submit' => array('form_test_load_include_submit'),
    '#ajax' => array(
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
 * Ajax callback for the file inclusion via menu test. We don't need to return
 * anything as the messages are added automatically.
 */
function form_test_load_include_menu_ajax($form) {
  return '';
}
