<?php

namespace Drupal\file\Plugin\migrate\cckfield\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "file",
 *   core = {7}
 * )
 */
class FileField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'filefield_widget' => 'file_generic',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'file_default',
      'url_plain' => 'file_url_plain',
      'path_plain' => 'file_url_plain',
      'image_plain' => 'image',
      'image_nodelink' => 'image',
      'image_imagelink' => 'image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'target_id' => 'fid',
        'display' => 'display',
        'description' => 'description',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return $row->getSourceProperty('widget_type') == 'imagefield_widget' ? 'image' : 'file';
  }

}
