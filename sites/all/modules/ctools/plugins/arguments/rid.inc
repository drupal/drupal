<?php
// $Id: rid.inc,v 1.2 2010/10/15 21:13:04 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a node revision id
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Revision: ID"),
  'keyword' => 'revision',
  'description' => t('Creates a node context from a revision ID argument.'),
  'context' => 'ctools_argument_rid_context',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the revision ID of a node for this argument'),
  ),
);

/**
 * Discover if this argument gives us the node we crave.
 */
function ctools_argument_rid_context($arg = NULL, $conf = NULL, $empty = FALSE) {
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

  $nid = db_result(db_query("SELECT nid FROM {node_revisions} WHERE vid = %d", $arg));
  $node = node_load($nid, $arg);
  if (!$node) {
    return FALSE;
  }

  return ctools_context_create('node', $node);
}

