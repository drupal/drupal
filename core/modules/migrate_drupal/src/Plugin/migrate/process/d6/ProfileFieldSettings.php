<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\ProfileFieldSettings.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_profile_field_settings"
 * )
 */
class ProfileFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Set the profile field settings configuration.
   */
  public function transform($type, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $settings = array();
    switch ($type) {
      case 'date':
        $settings['datetime_type'] = 'date';
        break;
    }
    return $settings;
  }

}
