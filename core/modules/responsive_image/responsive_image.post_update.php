<?php

/**
 * @file
 * Post update functions for Responsive Image.
 */

/**
 * Implements hook_removed_post_updates().
 */
function responsive_image_removed_post_updates() {
  return [
    'responsive_image_post_update_recreate_dependencies' => '9.0.0',
  ];
}
