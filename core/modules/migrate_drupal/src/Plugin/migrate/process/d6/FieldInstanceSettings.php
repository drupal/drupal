<?php
/**
 * @file
 * Contains Drupal\migrate_drupal\Plugin\migrate\d6\FieldInstanceSettings
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_field_field_settings"
 * )
 */
class FieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set the field instance defaults.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($widget_type, $widget_settings, $field_settings) = $value;
    $settings = array();
    switch ($widget_type) {
      case 'number':
        $settings['min'] = $field_settings['min'];
        $settings['max'] = $field_settings['max'];
        $settings['prefix'] = $field_settings['prefix'];
        $settings['suffix'] = $field_settings['suffix'];
        break;

      case 'link':
        // $settings['url'] = $widget_settings['default_value'][0]['url'];
        // D6 has optional, required, value and none. D8 only has disabled (0)
        // optional (1) and required (2).
        $map = array('disabled' => 0, 'optional' => 1, 'required' => 2);
        $settings['title'] = $map[$field_settings['title']];
        break;

      case 'filefield_widget':
        $settings['file_extensions'] = $widget_settings['file_extensions'];
        $settings['file_directory'] = $widget_settings['file_path'];
        $settings['description_field'] = $field_settings['description_field'];
        $settings['max_filesize'] = $this->convertSizeUnit($widget_settings['max_filesize_per_file']);
        break;

      case 'imagefield_widget':
        $settings['file_extensions'] = $widget_settings['file_extensions'];
        $settings['file_directory'] = 'public://';
        $settings['max_filesize'] = $this->convertSizeUnit($widget_settings['max_filesize_per_file']);
        $settings['alt_field'] = $widget_settings['alt'];
        $settings['alt_field_required'] = $widget_settings['custom_alt'];
        $settings['title_field'] = $widget_settings['title'];
        $settings['title_field_required'] = $widget_settings['custom_title'];
        $settings['max_resolution'] = $widget_settings['max_resolution'];
        $settings['min_resolution'] = $widget_settings['min_resolution'];
        break;

    }
    return $settings;
  }

  /**
   * Convert file size strings into their D8 format.
   *
   * D6 stores file size using a "K" for kilobytes and "M" for megabytes where
   * as D8 uses "KB" and "MB" respectively.
   *
   * @param string $size_string
   *   The size string, eg 10M
   *
   * @return string
   *   The D8 version of the size string.
   */
  protected function convertSizeUnit($size_string) {
    $size_unit = substr($size_string, strlen($size_string) - 1);
    if ($size_unit == "M" || $size_unit == "K") {
      return $size_string . "B";
    }
    return $size_string;
  }

}
