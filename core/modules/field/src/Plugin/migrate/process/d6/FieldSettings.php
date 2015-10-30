<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\process\d6\FieldSettings.
 */

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($field_type, $global_settings) = $value;
    return $this->getSettings($field_type, $global_settings);
  }

  /**
   * Merge the default D8 and specified D6 settings.
   *
   * @param string $field_type
   *   The field type.
   * @param array $global_settings
   *   The field settings.
   *
   * @return array
   *   A valid array of settings.
   */
  public function getSettings($field_type, $global_settings) {
    $max_length = isset($global_settings['max_length']) ? $global_settings['max_length'] : '';
    $max_length = empty($max_length) ? 255 : $max_length;
    $allowed_values = [];
    if (isset($global_settings['allowed_values'])) {
      $list = explode("\n", $global_settings['allowed_values']);
      $list = array_map('trim', $list);
      $list = array_filter($list, 'strlen');
      switch ($field_type) {
        case 'list_string':
        case 'list_integer':
        case 'list_float':
          foreach ($list as $value) {
            $value = explode("|", $value);
            $allowed_values[$value[0]] = isset($value[1]) ? $value[1] : $value[0];
          }
          break;

        default:
          $allowed_values = $list;
      }
    }

    $settings = array(
      'text' => array(
        'max_length' => $max_length,
      ),
      'datetime' => array('datetime_type' => 'datetime'),
      'list_string' => array(
        'allowed_values' => $allowed_values,
      ),
      'list_integer' => array(
        'allowed_values' => $allowed_values,
      ),
      'list_float' => array(
        'allowed_values' => $allowed_values,
      ),
      'boolean' => array(
        'allowed_values' => $allowed_values,
      ),
    );

    return isset($settings[$field_type]) ? $settings[$field_type] : array();
  }

}
