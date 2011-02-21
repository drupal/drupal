<?php
// $Id: node_form_log.inc,v 1.3 2010/10/11 22:18:23 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'icon' => 'icon_node_form.png',
  'title' => t('Node form revision log message'),
  'description' => t('Revision log message for the node.'),
  'required context' => new ctools_context_required(t('Form'), 'node_form'),
  'category' => t('Form'),
);

function ctools_node_form_log_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  // @todo -- this was never implemented!?

  return $block;
}

function ctools_node_form_log_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form revision log', array('@s' => $context->identifier));
}

function ctools_node_form_log_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
