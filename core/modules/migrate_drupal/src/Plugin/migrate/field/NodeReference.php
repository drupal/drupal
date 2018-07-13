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
 *   source_module = "nodereference",
 *   destination_module = "core",
 * )
 */
class NodeReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          'plugin' => 'get',
          'source' => 'nid',
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
