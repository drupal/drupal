<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Gives us a chance to set per field defaults.
 *
 * @MigrateProcessPlugin(
 *   id = "d6_field_type_defaults"
 * )
 */
class FieldTypeDefaults extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      if ($row->getSourceProperty('module') == 'date') {
        $value = 'datetime_default';
      }
      else {
        throw new MigrateException(sprintf('Failed to lookup field type %s in the static map.', var_export($value, TRUE)));
      }
    }
    return $value;
  }

}
