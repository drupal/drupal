<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\process\ProfileFieldSettings.
 */

namespace Drupal\user\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "profile_field_settings"
 * )
 */
class ProfileFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($type, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $settings = array();
    switch ($type) {
      case 'date':
        $settings['datetime_type'] = 'date';
        break;
    }
    return $settings;
  }

}
