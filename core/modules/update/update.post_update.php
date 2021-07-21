<?php

/**
 * @file
 * Post update functions for the Update Manager module.
 */

/**
 * Set default value for system notifications.
 */
function update_post_update_system_notifications_default_settings() {
  \Drupal::configFactory()
    ->getEditable('update.settings')
    ->set('check.update_system_notifications', TRUE)
    ->save();
}
