<?php

/**
 * @file
 * Post update functions for Migrate Drupal.
 */

/**
 * @addtogroup updates-8.9.x
 * @{
 */

/**
 * Uninstall migrate_drupal_multilingual since migrate_drupal is installed.
 */
function migrate_drupal_post_update_uninstall_multilingual() {
  \Drupal::service('module_installer')->uninstall(['migrate_drupal_multilingual']);
}

/**
 * @} End of "addtogroup updates-8.9.x".
 */
