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

    switch ($row->getSourceProperty('type')) {
      case 'image':
        if (!is_array($value['default_image'])) {
          $value['default_image'] = array('uuid' => '');
        }
        break;

      case 'taxonomy_term_reference':
        $value['target_type'] = 'taxonomy_term';
        break;

      default:
        break;
    }

    return $value;
  }

}
