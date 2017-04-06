<?php

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\cckfield\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "filefield",
 *   core = {6}
 * )
 */
class FileField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $migration->mergeProcessOfProperty($field_name, [
      'class' => __CLASS__,
    ]);
  }

}
