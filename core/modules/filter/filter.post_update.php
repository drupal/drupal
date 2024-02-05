<?php

/**
 * @file
 * Post update functions for Filter.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;

/**
 * Sorts filter format filter configuration.
 */
function filter_post_update_sort_filters(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'filter_format', function (FilterFormat $format): bool {
    $sorted_filters = $filters = array_keys($format->get('filters'));
    sort($sorted_filters);
    return $sorted_filters !== $filters;
  });
}

/**
 * Change filter_settings to type mapping.
 */
function filter_post_update_consolidate_filter_config(?array &$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'filter_format', function (FilterFormatInterface $format): bool {
    foreach ($format->get('filters') as $config) {
      if (empty($config['id']) || empty($config['provider'])) {
        return TRUE;
      }
    }
    return FALSE;
  });
}
