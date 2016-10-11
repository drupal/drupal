<?php

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "d6_no_core_version_specified"
 * )
 */
class D6NoCoreVersionSpecified extends FieldPluginBase {

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
