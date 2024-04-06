<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\ReferenceBase;

/**
 * MigrateField plugin for Drupal 7 node_reference fields.
 */
#[MigrateField(
  id: 'node_reference',
  core: [7],
  type_map: [
    'node_reference' => 'entity_reference',
  ],
  source_module: 'node_reference',
  destination_module: 'core',
)]
class NodeReference extends ReferenceBase {

  /**
   * The plugin ID for the reference type migration.
   *
   * @var string
   */
  protected $nodeTypeMigration = 'd7_node_type';

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

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'node_reference_default' => 'entity_reference_label',
      'node_reference_plain' => 'entity_reference_label',
      'node_reference_nid' => 'entity_reference_entity_id',
      'node_reference_node' => 'entity_reference_entity_view',
      'node_reference_path' => 'entity_reference_label',
    ];
  }

}
