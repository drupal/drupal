<?php
// $Id: user_from_node.inc,v 1.7 2010/01/29 20:18:02 merlinofchaos Exp $

/**
 * @file
 * Plugin to provide an relationship handler for node from user.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Node author'),
  'keyword' => 'user',
  'description' => t('Creates the author of a node as a user context.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'ctools_user_from_node_context',
);

/**
 * Return a new context based on an existing context.
 */
function ctools_user_from_node_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data) || !isset($context->data->uid)) {
    return ctools_context_create_empty('user', NULL);
  }

  if (isset($context->data->uid)) {
    // Load the user that is the author of the node.
    $uid = $context->data->uid;
    $account = user_load($uid);

    // Send it to ctools.
    return ctools_context_create('user', $account);
  }
}
