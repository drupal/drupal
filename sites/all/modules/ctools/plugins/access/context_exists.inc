<?php
// $Id: context_exists.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control/visibility based on existence of a specified context
 */

$plugin = array(
  'title' => t("Context exists"),
  'description' => t('Control access by whether or not a context exists and contains data.'),
  'callback' => 'ctools_context_exists_ctools_access_check',
  'settings form' => 'ctools_context_exists_ctools_access_settings',
  'summary' => 'ctools_context_exists_ctools_access_summary',
  'required context' => new ctools_context_required(t('Context'), 'any'),
  'defaults' => array('exists' => TRUE),
);

/**
 * Settings form
 */
function ctools_context_exists_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['exists'] = array(
    '#type' => 'radios',
    '#description' => t("Check to see if the context exists (contains data) or does not exist (contains no data). For example, if a context is optional and the path does not contain an argument for that context, it will not exist."),
    '#options' => array(TRUE => t('Exists'), FALSE => t("Doesn't exist")),
    '#default_value' => $conf['exists'],
  );
  return $form;
}

/**
 * Check for access
 */
function ctools_context_exists_ctools_access_check($conf, $context) {
  // xor returns false if the two bools are the same, and true if they are not.
  // i.e, if we asked for context_exists and it does, return true.
  // If we asked for context does not exist and it does, return false.
  return (empty($context->data) xor !empty($conf['exists']));
}

/**
 * Provide a summary description based upon the specified context
 */
function ctools_context_exists_ctools_access_summary($conf, $context) {
  if (!empty($conf['exists'])) {
    return t('@identifier exists', array('@identifier' => $context->identifier));
  }
  else {
    return t('@identifier does not exist', array('@identifier' => $context->identifier));
  }
}
