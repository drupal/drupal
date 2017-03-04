<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_field_instance_settings"
 * )
 */
class FieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($instance_settings, $widget_settings, $field_settings) = $value;
    $widget_type = $widget_settings['type'];

    // Get entityreference handler settings from source field configuration.
    if ($row->getSourceProperty('type') == "entityreference") {
      $instance_settings['handler'] = 'default:' . $field_settings['target_type'];
      // Transform the sort settings to D8 structure.
      $sort = [
        'field' => '_none',
        'direction' => 'ASC',
      ];
      if (!empty(array_filter($field_settings['handler_settings']['sort']))) {
        if ($field_settings['handler_settings']['sort']['type'] == "property") {
          $sort = [
            'field' => $field_settings['handler_settings']['sort']['property'],
            'direction' => $field_settings['handler_settings']['sort']['direction'],
          ];
        }
        elseif ($field_settings['handler_settings']['sort']['type'] == "field") {
          $sort = [
            'field' => $field_settings['handler_settings']['sort']['field'],
            'direction' => $field_settings['handler_settings']['sort']['direction'],
          ];
        }
      }
      if (empty($field_settings['handler_settings']['target_bundles'])) {
        $field_settings['handler_settings']['target_bundles'] = NULL;
      }
      $field_settings['handler_settings']['sort'] = $sort;
      $instance_settings['handler_settings'] = $field_settings['handler_settings'];
    }

    switch ($widget_type) {
      case 'image_image':
        $settings = $instance_settings;
        $settings['default_image'] = [
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
          'uuid' => '',
        ];
        break;

      default:
        $settings = $instance_settings;
    }

    return $settings;
  }

}
