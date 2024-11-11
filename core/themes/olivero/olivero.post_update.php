<?php

/**
 * @file
 * Post update functions for Olivero.
 */

/**
 * Implements hook_removed_post_updates().
 */
function olivero_removed_post_updates(): array {
  return [
    'olivero_post_update_add_olivero_primary_color' => '11.0.0',
  ];
}
