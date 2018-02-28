<?php

namespace Drupal\Core\Field\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "entityreference",
 *   type_map = {
 *     "entityreference" = "entity_reference",
 *   },
 *   core = {7},
 *   source_module = "entityreference",
 *   destination_module = "core"
 * )
 */
class EntityReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'entityreference_label' => 'entity_reference_label',
      'entityreference_entity_id' => 'entity_reference_entity_id',
      'entityreference_entity_view' => 'entity_reference_entity_view',
    ];
  }

}
