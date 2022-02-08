<?php

/**
 * @file
 * Post update functions for Hal.
 */

/**
 * Implements hook_removed_post_updates().
 */
function hal_removed_post_updates() {
  return [
    'hal_post_update_delete_settings' => '10.0.0',
  ];
}
