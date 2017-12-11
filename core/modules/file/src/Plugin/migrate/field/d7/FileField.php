<?php

namespace Drupal\file\Plugin\migrate\field\d7;

use Drupal\file\Plugin\migrate\field\d6\FileField as D6FileField;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "file",
 *   core = {7}
 * )
 */
class FileField extends D6FileField {

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'fid',
        'display' => 'display',
        'description' => 'description',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
