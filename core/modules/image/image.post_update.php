<?php

/**
 * @file
 * Post-update functions for Image.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\image\ImageConfigUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function image_removed_post_updates() {
  return [
    'image_post_update_image_style_dependencies' => '9.0.0',
    'image_post_update_scale_and_crop_effect_add_anchor' => '9.0.0',
  ];
}

/**
 * Add the image loading attribute setting to image field formatter instances.
 */
function image_post_update_image_loading_attribute(?array &$sandbox = NULL): void {
  $image_config_updater = \Drupal::classResolver(ImageConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($image_config_updater): bool {
    return $image_config_updater->processImageLazyLoad($view_display);
  });
}
