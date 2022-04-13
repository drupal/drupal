<?php

/**
 * @file
 * Post-update functions for Image.
 */

/**
 * Implements hook_removed_post_updates().
 */
function image_removed_post_updates() {
  return [
    'image_post_update_image_style_dependencies' => '9.0.0',
    'image_post_update_scale_and_crop_effect_add_anchor' => '9.0.0',
  ];
}
