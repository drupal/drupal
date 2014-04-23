<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\FieldSettings
 */

namespace Drupal\migrate_drupal\Plugin\migrate\Process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get the field settings.
 *
 * @MigrateProcessPlugin(
 *   id = "field_settings"
 * )
 */
class FieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Get the field default/mapped settings.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($field_type, $global_settings, $widget_settings) = $value;
    return $this->getSettings($field_type, $global_settings, $widget_settings);
  }

  /**
   * Merge the default D8 and specified D6 settings.
   *
   * @param string $field_type
   *   The field type.
   * @param array $global_settings
   *   The field settings.
   * @param array $widget_settings
   *   The widget settings needed for some settings.
   *
   * @return array
   *   A valid array of settings.
   */
  public function getSettings($field_type, $global_settings, $widget_settings) {
    $image_label = isset($widget_settings['alt']) ? $widget_settings['alt'] : '';
    $title_label = isset($widget_settings['title']) ? $widget_settings['title'] : '';
    $max_length = isset($global_settings['max_length']) ? $global_settings['max_length'] : '';
    $max_length = empty($max_length) ? 255 : $max_length;

    $settings = array(
      'text' => array(
        'max_length' => $max_length,
      ),
      'image' => array(
        'column_groups' => array(
          'alt' => array('label' => $image_label),
          'title' => array('label' => $title_label),
        ),
      ),
      'datetime' => array('datetime_type' => 'datetime'),
    );

    return isset($settings[$field_type]) ? $settings[$field_type] : array();
  }

}
