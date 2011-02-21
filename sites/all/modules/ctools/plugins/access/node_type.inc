<?php
// $Id: node_type.inc,v 1.12 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based upon node type.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Node: type"),
  'description' => t('Control access by node_type.'),
  'callback' => 'ctools_node_type_ctools_access_check',
  'default' => array('type' => array()),
  'settings form' => 'ctools_node_type_ctools_access_settings',
  'settings form submit' => 'ctools_node_type_ctools_access_settings_submit',
  'summary' => 'ctools_node_type_ctools_access_summary',
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'restrictions' => 'ctools_node_type_ctools_access_restrictions',
);

/**
 * Settings form for the 'by node_type' access plugin
 */
function ctools_node_type_ctools_access_settings($form, &$form_state, $conf) {
  $types = node_type_get_types();
  foreach ($types as $type => $info) {
    $options[$type] = check_plain($info->name);
  }

  $form['settings']['type'] = array(
    '#title' => t('Node type'),
    '#type' => 'checkboxes',
    '#options' => $options,
    '#description' => t('Only the checked node types will be valid.'),
    '#default_value' => $conf['type'],
  );
  return $form;
}

/**
 * Compress the node_types allowed to the minimum.
 */
function ctools_node_type_ctools_access_settings_submit($form, &$form_state) {
  $form_state['values']['settings']['type'] = array_filter($form_state['values']['settings']['type']);
}

/**
 * Check for access.
 */
function ctools_node_type_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  if (empty($context) || empty($context->data) || empty($context->data->type)) {
    return FALSE;
  }

  if (array_filter($conf['type']) && empty($conf['type'][$context->data->type])) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Inform the UI that we've eliminated a bunch of possibilities for this
 * context.
 */
function ctools_node_type_ctools_access_restrictions($conf, &$context) {
  if (isset($context->restrictions['type'])) {
    $context->restrictions['type'] = array_unique(array_merge($context->restrictions['type'], array_keys(array_filter($conf['type']))));
  }
  else {
    $context->restrictions['type'] = array_keys(array_filter($conf['type']));
  }
}

/**
 * Provide a summary description based upon the checked node_types.
 */
function ctools_node_type_ctools_access_summary($conf, $context) {
  if (!isset($conf['type'])) {
    $conf['type'] = array();
  }
  $types = node_type_get_types();

  $names = array();
  foreach (array_filter($conf['type']) as $type) {
    $names[] = check_plain($types[$type]->name);
  }

  if (empty($names)) {
    return t('@identifier is any node type', array('@identifier' => $context->identifier));
  }

  return format_plural(count($names), '@identifier is type "@types"', '@identifier type is one of "@types"', array('@types' => implode(', ', $names), '@identifier' => $context->identifier));
}

