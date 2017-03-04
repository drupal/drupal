<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_field_instance_defaults"
 * )
 */
class FieldInstanceDefaults extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($default_value, $widget_settings) = $value;
    $widget_type = $widget_settings['type'];

    $default = [];

    foreach ($default_value as $item) {
      switch ($widget_type) {
        // Add special processing here if needed.
        default:
          $default[] = $item;
      }
    }

    return $default;
  }

}
