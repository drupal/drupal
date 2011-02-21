<?php
// $Id: node_created.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node created date'),
  'icon' => 'icon_node.png',
  'description' => t('The date the referenced node was created.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'format' => 'small',
  ),
);

/**
 * Render the custom content type.
 */
function ctools_node_created_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Get a shortcut to the node.
  $node = $context->data;

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'node_created';
  $block->title   = t('Created date');
  $block->content = format_date($node->created, $conf['format']);
  $block->delta   = $node->nid;

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_node_created_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['format'] = array(
    '#title' => t('Date format'),
    '#type' => 'select',
    '#options' => array(
      'small' => format_date(REQUEST_TIME, 'small'),
      'medium' => format_date(REQUEST_TIME, 'medium'),
      'large' => format_date(REQUEST_TIME, 'large'),
    ),
    '#default_value' => $conf['format'],
  );
  return $form;
}

/**
 * Submit handler for the custom type settings form.
 */
function ctools_node_created_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_node_created_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" created date', array('@s' => $context->identifier));
}
