<?php

/**
 * @file
 * Post update functions for the Database Logging module.
 */

/**
 * Ensures the `dblog.settings` config has a langcode.
 */
function dblog_post_update_add_langcode_to_settings(): void {
  $config = \Drupal::configFactory()->getEditable('dblog.settings');
  if ($config->get('langcode')) {
    return;
  }
  $config->set('langcode', \Drupal::languageManager()->getDefaultLanguage()->getId())
    ->save();
}

/**
 * Implements hook_removed_post_updates().
 */
function dblog_removed_post_updates() {
  return [
    'dblog_post_update_convert_recent_messages_to_view' => '9.0.0',
  ];
}
