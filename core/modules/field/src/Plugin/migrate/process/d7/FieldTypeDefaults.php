<?php

namespace Drupal\field\Plugin\migrate\process\d7;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Gives us a chance to set per field defaults.
 */
#[MigrateProcess('d7_field_type_defaults')]
class FieldTypeDefaults extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) && isset($value[1])) {
      return $value[1];
    }
    return $value;
  }

}
