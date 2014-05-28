<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\FieldSettingsDefaults.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutable;
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
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    // If the 1 index is set then the map missed.
    if (isset($value[1])) {
      $value = $row->getSourceProperty('module') == 'date' ? array('format_type' => 'fallback') : array();
    }
    return $value;
  }

}
