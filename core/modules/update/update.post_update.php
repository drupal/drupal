<?php

/**
 * @file
 * Post update functions for Update Manager.
 */

/**
 * Implements hook_removed_post_updates().
 */
function update_remove_post_updates() {
  return [
    'update_post_update_add_view_update_notifications_permission' => '10.0.0',
  ];
}
