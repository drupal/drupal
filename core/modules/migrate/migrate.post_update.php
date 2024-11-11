<?php

/**
 * @file
 * Post update functions for migrate.
 */

/**
 * Implements hook_removed_post_updates().
 */
function migrate_removed_post_updates(): array {
  return [
    'migrate_post_update_clear_migrate_source_count_cache' => '10.0.0',
  ];
}
