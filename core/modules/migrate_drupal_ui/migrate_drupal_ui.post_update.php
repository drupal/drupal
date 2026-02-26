<?php

/**
 * @file
 * Post update functions for Migrate Drupal UI.
 */

/**
 * Uninstall Migrate Drupal UI if installed.
 */
function migrate_drupal_ui_post_update_migrate_drupal_ui_uninstall(): void {
  if (\Drupal::moduleHandler()->moduleExists('migrate_drupal_ui')) {
    \Drupal::service('module_installer')->uninstall(['migrate_drupal_ui'], FALSE);
  }
}
