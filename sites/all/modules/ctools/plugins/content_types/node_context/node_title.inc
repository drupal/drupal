<?php
// $Id: node_title.inc,v 1.5 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node title'),
  'icon' => 'icon_node.png',
  'description' => t('The title of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'link' => TRUE,
  ),
);

/**
 * Render the custom content type.
 */
function ctools_node_title_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Get a shortcut to the node.
  $node = $context->data;

  // Load information about the node type.
  $type = node_type_get_type($node);

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'node_title';
  $block->title   = $type->title_label;
  $block->content = !empty($conf['link']) ? l($node->title, 'node/'. $node->nid) : check_plain($node->title);
  $block->delta   = $node->nid;

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_node_title_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['link'] = array(
    '#title' => t('Link to node'),
    '#type' => 'checkbox',
    '#default_value' => $conf['link'],
    '#description' => t('Check here to make the title link to the node.'),
  );
  return $form;
}

/**
 * Submit handler for the custom type settings form.
 */
function ctools_node_title_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_node_title_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" title', array('@s' => $context->identifier));
}
