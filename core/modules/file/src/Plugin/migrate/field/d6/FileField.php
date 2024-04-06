<?php

namespace Drupal\file\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore filefield imagefield imagelink nodelink
/**
 * MigrateField Plugin for Drupal 6 file fields.
 */
#[MigrateField(
  id: 'filefield',
  core: [6],
  source_module: 'filefield',
  destination_module: 'file',
)]
class FileField extends FieldPluginBase {

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
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'd6_field_file',
      'source' => $field_name,
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
