<?php

/**
 * @file
 * Post update functions for Responsive Image.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\responsive_image\ResponsiveImageConfigUpdater;
use Drupal\responsive_image\ResponsiveImageStyleInterface;

/**
 * Implements hook_removed_post_updates().
 */
function responsive_image_removed_post_updates() {
  return [
    'responsive_image_post_update_recreate_dependencies' => '9.0.0',
  ];
}

/**
 * Re-order mappings by breakpoint ID and descending numeric multiplier order.
 */
function responsive_image_post_update_order_multiplier_numerically(array &$sandbox = NULL): void {
  /** @var \Drupal\responsive_image\ResponsiveImageConfigUpdater $responsive_image_config_updater */
  $responsive_image_config_updater = \Drupal::classResolver(ResponsiveImageConfigUpdater::class);
  $responsive_image_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'responsive_image_style', function (ResponsiveImageStyleInterface $responsive_image_style) use ($responsive_image_config_updater): bool {
    return $responsive_image_config_updater->orderMultipliersNumerically($responsive_image_style);
  });
}

/**
 * Add the image loading settings to responsive image field formatter instances.
 */
function responsive_image_post_update_image_loading_attribute(array &$sandbox = NULL): void {
  $responsive_image_config_updater = \Drupal::classResolver(ResponsiveImageConfigUpdater::class);
  $responsive_image_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($responsive_image_config_updater): bool {
    return $responsive_image_config_updater->processResponsiveImageField($view_display);
  });
}
