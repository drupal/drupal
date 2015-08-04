<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\process\d6\FieldFormatterSettingsDefaults.
 */

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Set the default field settings.
 *
 * @MigrateProcessPlugin(
 *   id = "field_formatter_settings_defaults"
 * )
 */
class FieldFormatterSettingsDefaults extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set field formatter settings when the map didn't map: for date
   * formatters, the fallback format, for everything else, empty array.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If the 1 index is set then the map missed.
    if (isset($value[1])) {
      $module = $row->getSourceProperty('module');
      if ($module === 'date') {
        $value = array('format_type' => 'fallback');
      }
      elseif ($module === 'number') {
        // We have to do the lookup here in the process plugin because for
        // number we need to calculated the settings based on the type not just
        // the module which works well for other field types.
        return $this->numberSettings($row->getDestinationProperty('options/type'), $value[1]);
      }
      else {
        $value = array();
      }
    }
    return $value;
  }

  /**
   * @param string $type
   *   The field type.
   * @param $format
   *   The format selected for the field on the display.
   *
   * @return array
   *   The correct default settings.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function numberSettings($type, $format) {
    $map = [
      'number_decimal' => [
        'us_0' => [
          'scale' => 0,
          'decimal_separator' => '.',
          'thousand_separator' => ',',
          'prefix_suffix' => TRUE,
        ],
        'us_1' => [
          'scale' => 1,
          'decimal_separator' => '.',
          'thousand_separator' => ',',
          'prefix_suffix' => TRUE,
        ],
        'us_2' => [
          'scale' => 2,
          'decimal_separator' => '.',
          'thousand_separator' => ',',
          'prefix_suffix' => TRUE,
        ],
        'be_0' => [
          'scale' => 0,
          'decimal_separator' => ',',
          'thousand_separator' => '.',
          'prefix_suffix' => TRUE,
        ],
        'be_1' => [
          'scale' => 1,
          'decimal_separator' => ',',
          'thousand_separator' => '.',
          'prefix_suffix' => TRUE,
        ],
        'be_2' => [
          'scale' => 2,
          'decimal_separator' => ',',
          'thousand_separator' => '.',
          'prefix_suffix' => TRUE,
        ],
        'fr_0' => [
          'scale' => 0,
          'decimal_separator' => ',',
          'thousand_separator' => ' ',
          'prefix_suffix' => TRUE,
        ],
        'fr_1' => [
          'scale' => 1,
          'decimal_separator' => ',',
          'thousand_separator' => ' ',
          'prefix_suffix' => TRUE,
        ],
        'fr_2' => [
          'scale' => 2,
          'decimal_separator' => ',',
          'thousand_separator' => ' ',
          'prefix_suffix' => TRUE,
        ],
      ],
      'number_integer' => [
        'us_0' => [
          'thousand_separator' => ',',
          'prefix_suffix' => TRUE,
        ],
        'be_0' => [
          'thousand_separator' => '.',
          'prefix_suffix' => TRUE,
        ],
        'fr_0' => [
          'thousand_separator' => ' ',
          'prefix_suffix' => TRUE,
        ],
      ],
    ];

    return isset($map[$type][$format]) ? $map[$type][$format] : [];
  }

}
