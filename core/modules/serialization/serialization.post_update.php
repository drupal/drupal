<?php

/**
 * @file
 * Post update functions for Serialization module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function serialization_removed_post_updates(): array {
  return [
    'serialization_post_update_delete_settings' => '10.0.0',
  ];
}
