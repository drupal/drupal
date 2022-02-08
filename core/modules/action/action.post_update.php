<?php

/**
 * @file
 * Post update functions for Action module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function action_removed_post_updates() {
  return [
    'action_post_update_move_plugins' => '10.0.0',
    'action_post_update_remove_settings' => '10.0.0',
  ];
}
