<?php

/**
 * @file
 * Post update functions for Path Alias.
 */

/**
 * Implements hook_removed_post_updates().
 */
function path_alias_removed_post_updates() {
  return [
    'path_alias_post_update_drop_path_alias_status_index' => '11.0.0',
  ];
}
