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
<<<<<<< HEAD
 *   core = {6,7},
 *   source_module = "taxonomy",
 *   destination_module = "core",
=======
 *   core = {6,7}
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 * )
 */
class TaxonomyTermReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  public function getFieldFormatterMap() {
    return [
      'taxonomy_term_reference_link' => 'entity_reference_label',
    ];
  }

  /**
   * {@inheritdoc}
   */
=======
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
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
