<?php

namespace Drupal\file\Plugin\migrate\field\d7;

use Drupal\file\Plugin\migrate\field\d6\FileField as D6FileField;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "file",
<<<<<<< HEAD
 *   core = {7},
 *   source_module = "file",
 *   destination_module = "file"
=======
 *   core = {7}
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
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
