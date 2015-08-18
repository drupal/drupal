<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\process\d7\FieldSettings.
 */

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_field_settings"
 * )
 */
class FieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = $row->getSourceProperty('settings');

    if ($row->getSourceProperty('type') == 'image' && !is_array($value['default_image'])) {
      $value['default_image'] = array(
        'uuid' => '',
      );
    }

    return $value;
  }

}
