<?php
// $Id: node_author.inc,v 1.6 2011/01/02 16:44:11 merlinofchaos Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node author'),
  'icon' => 'icon_node.png',
  'description' => t('The author of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'link' => TRUE,
  ),
);

/**
 * Render the custom content type.
 */
function ctools_node_author_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Get a shortcut to the node.
  $node = $context->data;
  $user = user_load($node->uid);

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'node_author';
  $block->title   = t('Author');
  $block->content = !empty($conf['link']) ? theme('username', array('account' => $user, 'link_path' => 'user/'. $node->uid)) : check_plain(format_username($node));
  $block->delta   = $node->nid;

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_node_author_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['link'] = array(
    '#title' => t('Link to author profile'),
    '#type' => 'checkbox',
    '#default_value' => $conf['link'],
    '#description' => t('Check here to link to the node author profile.'),
  );
  return $form;
}

/**
 * Submit handler for the custom type settings form.
 */
function ctools_node_author_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_node_author_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" author', array('@s' => $context->identifier));
}
