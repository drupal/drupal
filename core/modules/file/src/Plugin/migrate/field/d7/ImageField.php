<?php

namespace Drupal\file\Plugin\migrate\field\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "image",
 *   core = {7}
 * )
 */
class ImageField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'target_id' => 'fid',
        'alt' => 'alt',
        'title' => 'title',
        'width' => 'width',
        'height' => 'height',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
