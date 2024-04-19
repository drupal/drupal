<?php

/**
 * @file
 * Contains post update functions.
 */

/**
 * Implements hook_removed_post_updates().
 */
function forum_removed_post_updates() {
  return [
    'forum_post_update_recreate_forum_index_rows' => '11.0.0',
  ];
}
