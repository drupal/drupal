<?php
// $Id: node_edit_form_from_node.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Plugin to provide an relationship handler for term from node.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Node edit form from node'),
  'keyword' => 'node_form',
  'description' => t('Adds node edit form from a node context.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'ctools_node_edit_form_from_node_context',
);

/**
 * Return a new context based on an existing context.
 */
function ctools_node_edit_form_from_node_context($context, $conf) {
  if (empty($context->data)) {
    return ctools_context_create_empty('node_edit_form', NULL);
  }

  if (isset($context->data->nid)) {
    return ctools_context_create('node_edit_form', $context->data);
  }
}
