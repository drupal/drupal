<?php

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "d6_file",
 *   core = {6},
 *   type_map = {
 *     "file" = "file"
 *   }
 * )
 */
class D6FileField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {}

}
