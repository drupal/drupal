<?php
// $Id: node_edit_form.inc,v 1.18 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 *
 * Plugin to provide a node_edit_form context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Node edit form"),
  'description' => t('A node edit form.'),
  'context' => 'ctools_context_create_node_edit_form',
  'edit form' => 'ctools_context_node_edit_form_settings_form',
  'defaults' => array('nid' => ''),
  'keyword' => 'node_edit',
  'context name' => 'node_edit_form',
  'convert list' => 'ctools_context_node_edit_convert_list',
  'convert' => 'ctools_context_node_edit_convert',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the node ID of a node for this argument:'),
  ),
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_node_edit_form($empty, $node = NULL, $conf = FALSE) {
  static $created;
  $context = new ctools_context(array('form', 'node_edit', 'node_form', 'node', 'node_edit_form'));
  $context->plugin = 'node_edit_form';

  if ($empty || (isset($created) && $created)) {
    return $context;
  }
  $created = TRUE;

  if ($conf) {
    // In this case, $node is actually our $conf array.
    $nid = is_array($node) && isset($node['nid']) ? $node['nid'] : (is_object($node) ? $node->nid : 0);

    if (module_exists('translation')) {
      if ($translation = module_invoke('translation', 'node_nid', $nid, $GLOBALS['language']->language)) {
        $nid = $translation;
        $reload = TRUE;
      }
    }

    if (is_array($node) || !empty($reload)) {
      $node = node_load($nid);
    }
  }

  if (!empty($node)) {
    $form_id = $node->type . '_node_form';

    $form_state = array('want form' => TRUE, 'build_info' => array('args' => array($node)));

    $file = drupal_get_path('module', 'node') . '/node.pages.inc';
    require_once DRUPAL_ROOT . '/' . $file;
    // This piece of information can let other modules know that more files
    // need to be included if this form is loaded from cache:
    $form_state['form_load_files'] = array($file);

    $form = drupal_build_form($form_id, $form_state);

    // Fill in the 'node' portion of the context
    $context->data     = $node;
    $context->title    = $node->title;
    $context->argument = isset($node->nid) ? $node->nid : $node->type;

    $context->form       = $form;
    $context->form_state = &$form_state;
    $context->form_id    = $form_id;
    $context->form_title = $node->title;
    $context->node_type  = $node->type;
    $context->restrictions['type'] = array($node->type);
    return $context;
  }
}

function ctools_context_node_edit_form_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['node'] = array(
    '#title' => t('Enter the title or NID of a node'),
    '#type' => 'textfield',
    '#maxlength' => 512,
    '#autocomplete_path' => 'ctools/autocomplete/node',
    '#weight' => -10,
  );

  if (!empty($conf['nid'])) {
    $info = db_query('SELECT * FROM {node} WHERE nid = :nid', array(':nid' => $conf['nid']))->fetchObject();
    if ($info) {
      $link = l(t("'%title' [node id %nid]", array('%title' => $info->title, '%nid' => $info->nid)), "node/$info->nid", array('attributes' => array('target' => '_blank', 'title' => t('Open in new window')), 'html' => TRUE));
      $form['node']['#description'] = t('Currently set to !link', array('!link' => $link));
    }
  }

  $form['set_identifier'] = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#title' => t('Reset identifier to node title'),
    '#description' => t('If checked, the identifier will be reset to the node title of the selected node.'),
  );

  return $form;
}

/**
 * Validate a node.
 */
function ctools_context_node_edit_form_settings_form_validate($form, &$form_state) {
  // Validate the autocomplete
  if (empty($form_state['values']['nid']) && empty($form_state['values']['node'])) {
    form_error($form['node'], t('You must select a node.'));
    return;
  }

  if (empty($form_state['values']['node'])) {
    return;
  }

  $nid          = $form_state['values']['node'];
  $preg_matches = array();
  $match        = preg_match('/\[nid: (\d+)\]/', $nid, $preg_matches);
  if (!$match) {
    $match = preg_match('/^nid: (\d+)/', $nid, $preg_matches);
  }

  if ($match) {
    $nid = $preg_matches[1];
  }
  if (is_numeric($nid)) {
    $node = db_query('SELECT nid FROM {node} WHERE nid = :nid', array(':nid' => $nid))->fetchObject();
  }
  else {
    $node = db_query('SELECT nid FROM {node} WHERE LOWER(title) = LOWER(:title)', array(':title' => $nid))->fetchObject();
  }

  if (!$node) {
    form_error($form['node'], t('Invalid node selected.'));
  }
  else {
    form_set_value($form['nid'], $node->nid, $form_state);
    // $form_state['values']['nid'] = $node->nid;
  }
}

function ctools_context_node_edit_form_settings_form_submit($form, &$form_state) {
  if ($form_state['values']['set_identifier']) {
    $node = node_load($form_state['values']['nid']);
    $form_state['values']['identifier'] = $node->title;
  }

  // This will either be the value set previously or a value set by the
  // validator.
  $form_state['conf']['nid'] = $form_state['values']['nid'];
}

/**
 * Provide a list of ways that this context can be converted to a string.
 */
function ctools_context_node_edit_convert_list() {
  // Pass through to the "node" context convert list.
  $plugin = ctools_get_context('node');
  return ctools_context_node_convert_list();
}

/**
 * Convert a context into a string.
 */
function ctools_context_node_edit_convert($context, $type) {
  // Pass through to the "node" context convert list.
  $plugin = ctools_get_context('node');
  return ctools_context_node_convert($context, $type);
}
