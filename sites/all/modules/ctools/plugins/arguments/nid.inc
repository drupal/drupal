<?php
// $Id: nid.inc,v 1.10 2010/01/29 20:18:01 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a node id
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Node: ID"),
  'keyword' => 'node',
  'description' => t('Creates a node context from a node ID argument.'),
  'context' => 'ctools_argument_nid_context',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the node ID of a node for this argument'),
  ),
);

/**
 * Discover if this argument gives us the node we crave.
 */
function ctools_argument_nid_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('node');
  }

  // We can accept either a node object or a pure nid.
  if (is_object($arg)) {
    return ctools_context_create('node', $arg);
  }

  if (!is_numeric($arg)) {
    return FALSE;
  }

  $node = node_load($arg);
  if (!$node) {
    return FALSE;
  }

  return ctools_context_create('node', $node);
}

