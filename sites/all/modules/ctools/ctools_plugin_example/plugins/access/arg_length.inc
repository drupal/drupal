<?php
// $Id: arg_length.inc,v 1.3 2010/09/07 09:02:50 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control/visibility based on length of
 * simplecontext argument (in URL).
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Arg length"),
  'description' => t('Control access by length of simplecontext argument.'),
  'callback' => 'ctools_plugin_example_arg_length_ctools_access_check',
  'settings form' => 'ctools_plugin_example_arg_length_ctools_access_settings',
  'summary' => 'ctools_plugin_example_arg_length_ctools_access_summary',
  'required context' => new ctools_context_required(t('Simplecontext'), 'simplecontext'),
);

/**
 * Settings form for the 'by role' access plugin.
 */
function ctools_plugin_example_arg_length_ctools_access_settings(&$form, &$form_state, $conf) {
  $form['settings']['greater_than'] = array(
    '#type' => 'radios',
    '#title' => t('Grant access if simplecontext argument length is'),
    '#options' => array(1 => t('Greater than'), 0 => t('Less than or equal to')),
    '#default_value' => $conf['greater_than'],
  );
  $form['settings']['arg_length'] = array(
    '#type' => 'textfield',
    '#title' => t('Length of simplecontext argument'),
    '#size' => 3,
    '#default_value' => $conf['arg_length'],
    '#description' => t('Access/visibility will be granted based on arg length.'),
  );
}

/**
 * Check for access.
 */
function ctools_plugin_example_arg_length_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  if (empty($context) || empty($context->data)) {
    return FALSE;
  }
  $compare = ($context->arg_length > $conf['arg_length']);
  if (($compare && $conf['greater_than']) || (!$compare && !$conf['greater_than'])) {
    return TRUE;
  }
  return FALSE;
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_plugin_example_arg_length_ctools_access_summary($conf, $context) {
  return t('Simpletext argument must be !comp @length characters',
    array('!comp' => $conf['greater_than'] ? 'greater than' : 'less than or equal to',
      '@length' => $conf['arg_length']));
}

