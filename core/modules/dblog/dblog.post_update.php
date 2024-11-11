<?php

/**
 * @file
 * Post update functions for the Database Logging module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function dblog_removed_post_updates(): array {
  return [
    'dblog_post_update_convert_recent_messages_to_view' => '9.0.0',
  ];
}
