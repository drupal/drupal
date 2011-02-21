<?php
// $Id: uid.inc,v 1.12 2010/01/29 20:18:01 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a user id
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("User: ID"),
  // keyword to use for %substitution
  'keyword' => 'user',
  'description' => t('Creates a user context from a user ID argument.'),
  'context' => 'ctools_argument_uid_context',
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the user ID of a user for this argument'),
  ),
  'default' => array('to_arg' => TRUE),
  'path placeholder' => '%pm_uid_arg', // This is in pagemanager.
  'path placeholder to_arg' => TRUE,
);

/**
 * Discover if this argument gives us the user we crave.
 */
function ctools_argument_uid_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('user');
  }

  // We can accept either a node object or a pure nid.
  if (is_object($arg)) {
    return ctools_context_create('user', $arg);
  }

  if (!is_numeric($arg)) {
    return NULL;
  }

  $account = user_load(array('uid' => $arg));
  if (!$account) {
    return NULL;
  }

  return ctools_context_create('user', $account);
}
