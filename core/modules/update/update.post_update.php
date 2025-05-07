<?php

/**
 * @file
 * Post update functions for Update Status.
 */

use Drupal\Core\Site\Settings;

/**
 * Implements hook_removed_post_updates().
 */
function update_remove_post_updates() {
  return [
    'update_post_update_add_view_update_notifications_permission' => '10.0.0',
    'update_post_update_set_blank_fetch_url_to_null' => '11.0.0',
  ];
}

/**
 * Removes the legacy 'Update Manager' disk cache.
 */
function update_post_update_clear_disk_cache(): void {
  // @see _update_manager_unique_id()
  $id = substr(hash('sha256', Settings::getHashSalt()), 0, 8);
  // List of legacy 'Update Manager' cache directories.
  $directories = [
    // @see _update_manager_cache_directory()
    "temporary://update-cache-$id",
    // @see _update_manager_extract_directory()
    "temporary://update-extraction-$id",
  ];
  foreach ($directories as $directory) {
    if (is_dir($directory)) {
      \Drupal::service('file_system')->deleteRecursive($directory);
    }
  }

}
