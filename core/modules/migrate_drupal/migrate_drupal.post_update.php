<?php

/**
 * @file
 * Post update functions for migrate_drupal.
 */

/**
 * Force MigrateField plugin definitions to be cleared.
 *
 * @see https://www.drupal.org/node/3006470
 */
function drupal_migrate_post_update_clear_migrate_field_plugin_cache() {
  // Empty post-update hook.
}

/**
 * Uninstall migrate_drupal_multilingual since migrate_drupal is installed.
 */
function migrate_drupal_post_update_uninstall_multilingual() {
  \Drupal::service('module_installer')->uninstall(['migrate_drupal_multilingual']);
}
