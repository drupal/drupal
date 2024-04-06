<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

#[MigrateProcess('d7_field_settings')]
class FieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = $row->getSourceProperty('settings');

    switch ($row->getSourceProperty('type')) {
      case 'image':
        if (!is_array($value['default_image'])) {
          $value['default_image'] = ['uuid' => ''];
        }
        break;

      case 'date':
      case 'datetime':
      case 'datestamp':
        $collected_date_attributes = is_numeric(array_keys($value['granularity'])[0])
          ? $value['granularity']
          : array_keys(array_filter($value['granularity']));
        if (empty(array_intersect($collected_date_attributes, ['hour', 'minute', 'second']))) {
          $value['datetime_type'] = 'date';
        }
        break;

      case 'taxonomy_term_reference':
        $value['target_type'] = 'taxonomy_term';
        break;

      case 'user_reference':
        $value['target_type'] = 'user';
        break;

      default:
        break;
    }

    return $value;
  }

}
