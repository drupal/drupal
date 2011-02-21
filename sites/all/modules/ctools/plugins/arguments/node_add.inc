<?php
// $Id: node_add.inc,v 1.6 2010/10/15 22:56:51 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a Node add form
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Node add form: node type"),
  // keyword to use for %substitution
  'keyword' => 'node_type',
  'description' => t('Creates a node add form context from a node type argument.'),
  'context' => 'ctools_node_add_context',
);

/**
 * Discover if this argument gives us the node we crave.
 */
function ctools_node_add_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if (!isset($arg)) {
    return ctools_context_create_empty('node_add_form');
  }

  return ctools_context_create('node_add_form', $arg);
}

