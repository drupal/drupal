<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "userreference",
 *   core = {6},
 *   type_map = {
 *     "userreference" = "entity_reference",
 *   },
 *   source_module = "userreference",
 *   destination_module = "core",
 * )
 */
class UserReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          'plugin' => 'migration_lookup',
          'migration' => 'd6_user',
          'source' => 'uid',
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
