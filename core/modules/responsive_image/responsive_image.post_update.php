<?php

/**
 * @file
 * Post update functions for Responsive Image.
 */

/**
 * Implements hook_removed_post_updates().
 */
function responsive_image_removed_post_updates(): array {
  return [
    'responsive_image_post_update_recreate_dependencies' => '9.0.0',
    'responsive_image_post_update_order_multiplier_numerically' => '11.0.0',
    'responsive_image_post_update_image_loading_attribute' => '11.0.0',
  ];
}
