<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\process\d6\FieldIdGenerator.
 */

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Generate the file name for field config entities.
 *
 * @MigrateProcessPlugin(
 *   id = "field_id_generator"
 * )
 */
class FieldIdGenerator extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value[0] . "." . $value[1];
  }

}
