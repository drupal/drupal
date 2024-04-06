<?php

namespace Drupal\node\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Migration destination for node type entity.
 */
#[MigrateDestination('entity:node_type')]
class EntityNodeType extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getEntity(Row $row, array $old_destination_id_values) {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = parent::getEntity($row, $old_destination_id_values);

    // Config schema does not allow description or help text to be empty.
    if ($node_type->getDescription() === '') {
      $node_type->set('description', NULL);
    }
    if ($node_type->getHelp() === '') {
      $node_type->set('help', NULL);
    }
    return $node_type;
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_ids = parent::import($row, $old_destination_id_values);
    if ($row->getDestinationProperty('create_body')) {
      $node_type = $this->storage->load(reset($entity_ids));
      node_add_body_field($node_type, $row->getDestinationProperty('create_body_label'));
    }
    return $entity_ids;
  }

}
