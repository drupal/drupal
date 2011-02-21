<?php
// $Id: user_name.inc,v 1.3 2010/01/29 20:18:01 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a username
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("User: name"),
  // keyword to use for %substitution
  'keyword' => 'user',
  'description' => t('Creates a user context from a user name.'),
  'context' => 'ctools_argument_user_name_context',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the username of a user for this argument'),
  ),
);

/**
 * Discover if this argument gives us the user we crave.
 */
function ctools_argument_user_name_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('user');
  }

  // We can accept either a node object or a pure nid.
  if (is_object($arg)) {
    return ctools_context_create('user', $arg);
  }

  $account = user_load(array('name' => $arg));
  if (!$account) {
    return NULL;
  }
  return ctools_context_create('user', $account);
}



