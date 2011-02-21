<?php
// $Id: node_add_form.inc,v 1.14 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 *
 * Plugin to provide a node_add_form context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Node add form'),
  'description' => t('A node add form.'),
  'context' => 'ctools_context_create_node_add_form',
  'edit form' => 'ctools_context_node_add_form_settings_form',
  'defaults' => array('type' => ''),
  'keyword' => 'node_add',
  'context name' => 'node_add_form',
  'convert list' => array('type' => t('Node type')),
  'convert' => 'ctools_context_node_add_form_convert',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the node type this context.'),
  ),
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_node_add_form($empty, $data = NULL, $conf = FALSE) {
  static $created;
  $context = new ctools_context(array('form', 'node_add', 'node_form'));
  $context->plugin = 'node_add_form';

  if ($empty || (isset($created) && $created)) {
    return $context;
  }
  $created = TRUE;

  if ($conf && (isset($data['types']) || isset($data['type']))) {
    // Holdover from typo'd config.
    $data = isset($data['types']) ? $data['types'] : $data['type'];
  }

  if (!empty($data)) {
    $types = node_get_types();
    $type = str_replace('-', '_', $data);

    // Validate the node type exists.
    if (isset($types[$type]) && node_access('create', $type)) {
      // Initialize settings:
      global $user;
      $node = array('uid' => $user->uid, 'name' => $user->name, 'type' => $type);

      $form_id = $node['type'] . '_node_form';

      $form_state = array('want form' => TRUE, 'build_info' => array('args' => array($node)));

      $file = drupal_get_path('module', 'node') . '/node.pages.inc';
      include_once './' . $file;
      // This piece of information can let other modules know that more files
      // need to be included if this form is loaded from cache:
      $form_state['form_load_files'] = array($file);

      $form = drupal_build_form($form_id, $form_state);
      // In a form, $data is the object being edited.
      $context->data     = $type;
      $context->title    = $types[$type]->name;
      $context->argument = $type;

      // These are specific pieces of data to this form.
      // All forms should place the form here.
      $context->form       = $form;
      $context->form_id    = $type . '_node_form';
      $context->form_title = t('Submit @name', array('@name' => $types[$type]->name));
      $context->node_type  = $type;
      $context->restrictions['type'] = array($type);
      return $context;
    }
  }
}

function ctools_context_node_add_form_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  foreach (node_get_types() as $type => $info) {
    $options[$type] = $info->name;
  }
  asort($options);

  $form['type'] = array(
    '#title' => t('Node type'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $conf['type'],
    '#description' => t('Select the node type for this form.'),
  );

  return $form;
}

function ctools_context_node_add_form_settings_form_submit($form, &$form_state) {
  $form_state['conf']['type'] = $form_state['values']['type'];
}

/**
 * Convert a context into a string.
 */
function ctools_context_node_add_form_convert($context, $type) {
  switch ($type) {
    case 'type':
      return $context->data;
  }
}
