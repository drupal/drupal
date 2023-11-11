<?php

/**
 * @file
 * Post update functions for Filter.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\filter\Entity\FilterFormat;

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
