<?php
// $Id: string_equal.inc,v 1.3 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control/visibility based on specified context string matching user-specified string
 */

$plugin = array(
  'title' => t("String: comparison"),
  'description' => t('Control access by string match.'),
  'callback' => 'ctools_string_equal_ctools_access_check',
  'settings form' => 'ctools_string_equal_ctools_access_settings',
  'summary' => 'ctools_string_equal_ctools_access_summary',
  'required context' => new ctools_context_required(t('String'), 'string'),
  'defaults' => array('operator' => '=', 'value' => '', 'case' => FALSE),
);

/**
 * Settings form
 */
function ctools_string_equal_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['operator'] = array(
    '#type' => 'radios',
    '#title' => t('Operator'),
    '#options' => array(
      '=' => t('Equal'),
      '!=' => t('Not equal'),
      'regex' => t('Regular expression'),
      '!regex' => t('Not equal to regular expression'),
    ),
    '#default_value' => $conf['operator'],
    '#description' => t('If using a regular expression, you should enclose the pattern in slashes like so: <em>/foo/</em>. If you need to compare against slashes you can use another character to enclose the pattern, such as @. See <a href="http://www.php.net/manual/en/reference.pcre.pattern.syntax.php">PHP regex documentation</a> for more.'),
  );

  $form['settings']['value'] = array(
    '#type' => 'textfield',
    '#title' => t('String'),
    '#default_value' => $conf['value'],
  );

  $form['settings']['case'] = array(
    '#type' => 'checkbox',
    '#title' => t('Case sensitive'),
    '#default_value' => $conf['case'],
  );
  return $form;
}

/**
 * Check for access
 */
function ctools_string_equal_ctools_access_check($conf, $context) {
  if (empty($context) || empty($context->data)) {
    $string = '';
  }
  else {
    $string = $context->data;
  }

  $value = $conf['value'];
  if (empty($conf['case'])) {
    $string = drupal_strtolower($string);
    $value = drupal_strtolower($value);
  }

  switch ($conf['operator']) {
    case '=':
      return $string === $value;
    case '!=':
      return $string !== $value;
    case 'regex':
      return preg_match($value, $string);
    case '!regex':
      return !preg_match($value, $string);
  }
}

/**
 * Provide a summary description based upon the specified context
 */
function ctools_string_equal_ctools_access_summary($conf, $context) {
  $values = array('@identifier' => $context->identifier, '@value' => $conf['value']);
  switch ($conf['operator']) {
    case '=':
      return t('@identifier is "@value"', $values);
    case '!=':
      return t('@identifier is not "@value"', $values);
    case 'regex':
      return t('@identifier matches "@value"', $values);
    case '!regex':
      return t('@identifier does not match "@value"', $values);
  }
}

