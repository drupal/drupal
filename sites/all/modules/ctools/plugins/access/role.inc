<?php
// $Id: role.inc,v 1.11 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based upon role membership.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("User: role"),
  'description' => t('Control access by role.'),
  'callback' => 'ctools_role_ctools_access_check',
  'default' => array('rids' => array()),
  'settings form' => 'ctools_role_ctools_access_settings',
  'settings form submit' => 'ctools_role_ctools_access_settings_submit',
  'summary' => 'ctools_role_ctools_access_summary',
  'required context' => new ctools_context_required(t('User'), 'user'),
);

/**
 * Settings form for the 'by role' access plugin
 */
function ctools_role_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['rids'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Role'),
    '#default_value' => $conf['rids'],
    '#options' => ctools_get_roles(),
    '#description' => t('Only the checked roles will be granted access.'),
  );
  return $form;
}

/**
 * Compress the roles allowed to the minimum.
 */
function ctools_role_ctools_access_settings_submit($form, &$form_state) {
  $form_state['values']['settings']['rids'] = array_keys(array_filter($form_state['values']['settings']['rids']));
}

/**
 * Check for access.
 */
function ctools_role_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  if (empty($context) || empty($context->data) || !isset($context->data->roles)) {
    return FALSE;
  }

  $roles = array_keys($context->data->roles);
  $roles[] = $context->data->uid ? DRUPAL_AUTHENTICATED_RID : DRUPAL_ANONYMOUS_RID;
  return (bool) array_intersect($conf['rids'], $roles);
}

/**
 * Provide a summary description based upon the checked roles.
 */
function ctools_role_ctools_access_summary($conf, $context) {
  if (!isset($conf['rids'])) {
    $conf['rids'] = array();
  }
  $roles = ctools_get_roles();

  $names = array();
  foreach (array_filter($conf['rids']) as $rid) {
    $names[] = check_plain($roles[$rid]);
  }

  if (empty($names)) {
    return t('@identifier can have any role', array('@identifier' => $context->identifier));
  }

  return format_plural(count($names), '@identifier has role "@roles"', '@identifier has one of "@roles"', array('@roles' => implode(', ', $names), '@identifier' => $context->identifier));
}

