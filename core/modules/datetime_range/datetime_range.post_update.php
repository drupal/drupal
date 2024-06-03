<?php

/**
 * @file
 * Post-update functions for Datetime Range module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangePlainFormatter;

/**
 * Implements hook_removed_post_updates().
 */
function datetime_range_removed_post_updates() {
  return [
    'datetime_range_post_update_translatable_separator' => '9.0.0',
    'datetime_range_post_update_views_string_plugin_id' => '9.0.0',
  ];
}

/**
 * Adds 'from_to' in flagged entity view date range formatter.
 *
 * @see \datetime_range_entity_view_display_presave
 */
function datetime_range_post_update_from_to_configuration(?array &$sandbox = NULL): void {
  /** @var \Drupal\Core\Field\FormatterPluginManager $field_formatter_manager */
  $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewDisplayInterface $entity_view_display) use ($field_formatter_manager) {
    foreach (array_values($entity_view_display->getComponents()) as $component) {
      if (empty($component['type'])) {
        continue;
      }

      $plugin_definition = $field_formatter_manager->getDefinition($component['type'], FALSE);
      $daterange_formatter_classes = [
        DateRangeCustomFormatter::class,
        DateRangeDefaultFormatter::class,
        DateRangePlainFormatter::class,
      ];

      if (!in_array($plugin_definition['class'], $daterange_formatter_classes, FALSE)) {
        continue;
      }

      if (!isset($component['settings']['from_to'])) {
        return TRUE;
      }
    }
    return FALSE;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}
