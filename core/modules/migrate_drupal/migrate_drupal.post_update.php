<?php

/**
 * @file
 * Post update functions for Migrate Drupal.
 */

/**
 * Uninstall Migrate Drupal if installed.
 */
function migrate_drupal_post_update_migrate_drupal_uninstall(): void {
  if (\Drupal::moduleHandler()->moduleExists('migrate_drupal')) {
    \Drupal::service('module_installer')->uninstall(['migrate_drupal'], FALSE);
  }
}
