<?php

/**
 * @file
 * Post update functions for Filter.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\filter\FilterFormatInterface;

/**
 * Implements hook_removed_post_updates().
 */
function filter_removed_post_updates(): array {
  return [
    'filter_post_update_sort_filters' => '11.0.0',
    'filter_post_update_consolidate_filter_config' => '11.0.0',
  ];
}

/**
 * Sort allowed_html config in filter formats.
 */
function filter_post_update_sort_allowed_html(?array &$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'filter_format', function (FilterFormatInterface $format): bool {
      foreach ($format->get('filters') as $config) {
        if ($config['id'] === 'filter_html' && $config['status'] === TRUE) {
          return TRUE;
        }
      }
      return FALSE;
    });
}
