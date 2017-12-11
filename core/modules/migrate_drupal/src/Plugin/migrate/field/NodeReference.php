<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "nodereference",
 *   core = {6},
 *   type_map = {
 *     "nodereference" = "entity_reference",
 *   },
 * )
 */
class NodeReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          'plugin' => 'migration_lookup',
          'migration' => 'd6_node',
          'source' => 'nid',
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
