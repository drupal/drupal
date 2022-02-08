<?php

/**
 * @file
 * Post update functions for Migrate Drupal.
 */

/**
 * Implements hook_removed_post_updates().
 */
function migrate_drupal_removed_post_updates() {
  return [
    'migrate_drupal_post_update_uninstall_multilingual' => '10.0.0',
  ];
}
