<?php

namespace Drupal\link\Plugin\migrate\field\d6;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "link",
 *   core = {6},
 *   type_map = {
 *     "link_field" = "link"
 *   },
 *   source_module = "link",
 *   destination_module = "link"
 * )
 */
class LinkField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    // See d6_field_formatter_settings.yml and FieldPluginBase
    // alterFieldFormatterMigration().
    return [
      'default' => 'link',
      'plain' => 'link',
      'absolute' => 'link',
      'title_plain' => 'link',
      'url' => 'link',
      'short' => 'link',
      'label' => 'link',
      'separate' => 'link_separate',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'field_link',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
