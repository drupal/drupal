<?php
// $Id: node_access.inc,v 1.10 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide access control based upon node type.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Node: accessible"),
  'description' => t('Control access with built in Drupal node access test.'),
  'callback' => 'ctools_node_access_ctools_access_check',
  'default' => array('type' => 'view'),
  'settings form' => 'ctools_node_access_ctools_access_settings',
  'settings form submit' => 'ctools_node_access_ctools_access_settings_submit',
  'summary' => 'ctools_node_access_ctools_access_summary',
  'required context' => array(
    new ctools_context_required(t('User'), 'user'),
    new ctools_context_required(t('Node'), 'node'),
  ),
);

/**
 * Settings form for the 'by node_access' access plugin
 */
function ctools_node_access_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['type'] = array(
    '#title' => t('Operation'),
    '#type' => 'radios',
    '#options' => array(
      'view' => t('View'),
      'update' => t('Update'),
      'delete' => t('Delete'),
      'create' => t('Create nodes of the same type'),
    ),
    '#description' => t('Using built in Drupal node access rules, determine if the user can perform the selected operation on the node.'),
    '#default_value' => $conf['type'],
  );
  return $form;
}

/**
 * Check for access.
 */
function ctools_node_access_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  list($user_context, $node_context) = $context;
  if (empty($node_context) || empty($node_context->data) || empty($node_context->data->type)) {
    return FALSE;
  }

  if (empty($user_context) || empty($user_context->data)) {
    return FALSE;
  }

  if ($conf['type'] == 'create') {
    return node_access('create', $node_context->data->type, $user_context->data);
  }
  else {
    return node_access($conf['type'], $node_context->data, $user_context->data);
  }
}

/**
 * Provide a summary description based upon the checked node_accesss.
 */
function ctools_node_access_ctools_access_summary($conf, $context) {
  list($user_context, $node_context) = $context;
  $replacement = array('@user' => $user_context->identifier, '@node' => $node_context->identifier);

  switch ($conf['type']) {
    case 'view':
      return t('@user can view @node.', $replacement);

    case 'update':
      return t('@user can edit @node.', $replacement);

    case 'delete':
      return t('@user can delete @node.', $replacement);

    case 'create':
      return t('@user can create nodes of the same type as @node.', $replacement);
  }
}

