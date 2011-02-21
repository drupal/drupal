<?php
// $Id: node_type_desc.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node type description'),
  'icon' => 'icon_node.png',
  'description' => t('Node type description.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
);

/**
 * Output function for the 'node' content type. Outputs a node
 * based on the module and delta supplied in the configuration.
 */
function ctools_node_type_desc_content_type_render($subtype, $conf, $panel_args, $context) {
  $node = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'node_type';

  if ($node) {
    $type = node_type_get_type($node);
    $block->title = $type->name;
    $block->content = filter_xss_admin($type->description);
    $block->delta   = $node->type;
  }
  else {
    $block->title = t('Node type description');
    $block->content = t('Node type description goes here.');
    $block->delta   = 'unknown';
  }

  return $block;
}

function ctools_node_type_desc_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" type description', array('@s' => $context->identifier));
}

function ctools_node_type_desc_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
