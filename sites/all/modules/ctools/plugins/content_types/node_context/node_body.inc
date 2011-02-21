<?php
// $Id: node_body.inc,v 1.6 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node body'),
  'icon' => 'icon_node.png',
  'description' => t('The body of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
);

/**
 * Render the custom content type.
 */
function ctools_node_body_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Get a shortcut to the node.
  $node = $context->data;

  // Load information about the node type.
  $type = node_type_get_type($node);

  // Do not render if the body is disabled for this node type.
  if (!$type->has_body) {
    return;
  }

  $body = str_replace('<!--break-->', '', $node->body);

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'node_body';
  $block->title   = $type->body_label;
  $block->content = check_markup($body, $node->format, FALSE);
  $block->delta   = $node->nid;

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_node_body_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}

/**
 * Returns the administrative title for a type.
 */
function ctools_node_body_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" body', array('@s' => $context->identifier));
}
