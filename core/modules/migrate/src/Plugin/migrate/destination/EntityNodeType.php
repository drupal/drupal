<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityNodeType.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:node_type"
 * )
 */
class EntityNodeType extends EntityConfigBase {

  /**
     * {@inheritdoc}
     */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $entity_ids = parent::import($row, $old_destination_id_values);
    if ($row->getDestinationProperty('create_body')) {
      $node_type = $this->storage->load(reset($entity_ids));
      node_add_body_field($node_type, $row->getDestinationProperty('create_body_label'));
    }
    return $entity_ids;
  }

}
