<?php

namespace Drupal\taxonomy\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "taxonomy_term_reference",
 *   type_map = {
 *     "taxonomy_term_reference" = "entity_reference"
 *   },
 *   core = {6,7},
 *   source_module = "taxonomy",
 *   destination_module = "core",
 * )
 */
class TaxonomyTermReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'taxonomy_term_reference_link' => 'entity_reference_label',
      'i18n_taxonomy_term_reference_link' => 'entity_reference_label',
      'entityreference_entity_view' => 'entity_reference_entity_view',
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
        'target_id' => 'tid',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
