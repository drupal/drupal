<?php
// $Id: string_length.inc,v 1.3 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control/visibility based on length of
 * a string context.
 */

$plugin = array(
  'title' => t("String: length"),
  'description' => t('Control access by length of string context.'),
  'callback' => 'ctools_string_length_ctools_access_check',
  'settings form' => 'ctools_string_length_ctools_access_settings',
  'summary' => 'ctools_string_length_ctools_access_summary',
  'required context' => new ctools_context_required(t('String'), 'string'),
  'defaults' => array('operator' => '=', 'length' => 0),
);

/**
 * Settings form for the 'by role' access plugin.
 */
function ctools_string_length_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['operator'] = array(
    '#type' => 'radios',
    '#title' => t('Operator'),
    '#options' => array(
      '>' => t('Greater than'),
      '>=' => t('Greater than or equal to'),
      '=' => t('Equal to'),
      '!=' => t('Not equal to'),
      '<' => t('Less than'),
      '<=' => t('Less than or equal to'),
    ),
    '#default_value' => $conf['operator'],
  );
  $form['settings']['length'] = array(
    '#type' => 'textfield',
    '#title' => t('Length of string'),
    '#size' => 3,
    '#default_value' => $conf['length'],
    '#description' => t('Access/visibility will be granted based on string context length.'),
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_string_length_ctools_access_check($conf, $context) {
  if (empty($context) || empty($context->data)) {
    $length = 0;
  }
  else {
    $length = drupal_strlen($context->data);
  }

  switch($conf['operator']) {
    case '<':
      return $length < $conf['length'];
    case '<=':
      return $length <= $conf['length'];
    case '==':
      return $length == $conf['length'];
    case '!=':
      return $length != $conf['length'];
    case '>':
      return $length > $conf['length'];
    case '>=':
      return $length >= $conf['length'];
  }
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_string_length_ctools_access_summary($conf, $context) {
  return t('@identifier must be @comp @length characters', array('@identifier' => $context->identifier, '@comp' => $conf['operator'], '@length' => $conf['length']));
}
