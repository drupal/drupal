<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d6;

// cspell:ignore nodereference

use Drupal\migrate_drupal\Plugin\migrate\field\ReferenceBase;

/**
 * MigrateField Plugin for Drupal 6 node reference fields.
 *
 * @MigrateField(
 *   id = "nodereference",
 *   core = {6},
 *   type_map = {
 *     "nodereference" = "entity_reference",
 *   },
 *   source_module = "nodereference",
 *   destination_module = "core",
 * )
 *
 * @internal
 */
class NodeReference extends ReferenceBase {

  /**
   * The plugin ID for the reference type migration.
   *
   * @var string
   */
  protected $nodeTypeMigration = 'd6_node_type';

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeMigrationId() {
    return $this->nodeTypeMigration;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityId() {
    return 'nid';
  }

}
