<?php

namespace Drupal\image\Plugin\migrate\field\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

#[MigrateField(
  id: 'image',
  core: [7],
  source_module: 'image',
  destination_module: 'image',
)]
class ImageField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'image' => 'image_default',
      'image_miw' => 'image_image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
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
