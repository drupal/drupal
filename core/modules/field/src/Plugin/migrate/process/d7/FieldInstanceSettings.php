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
    list($instance_settings, $widget_settings) = $value;
    $widget_type = $widget_settings['type'];

    switch ($widget_type) {
      case 'image_image':
        $settings = $instance_settings;
        $settings['default_image'] = array(
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
          'uuid' => '',
        );
        break;

      default:
        $settings = $instance_settings;
    }

    return $settings;
  }

}
