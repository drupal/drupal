<?php

/**
 * @file
 * Post update functions for Rest.
 */

/**
 * Implements hook_removed_post_updates().
 */
function rest_removed_post_updates() {
  return [
    'rest_post_update_create_rest_resource_config_entities' => '9.0.0',
    'rest_post_update_resource_granularity' => '9.0.0',
    'rest_post_update_161923' => '9.0.0',
  ];
}

/**
 * Remove obsolete rest.settings configuration.
 */
function rest_post_update_delete_settings() {
  \Drupal::configFactory()->getEditable('rest.settings')->delete();
}
