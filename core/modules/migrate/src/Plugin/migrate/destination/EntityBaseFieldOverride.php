<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityBaseFieldOverride.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:base_field_override"
 * )
 */
class EntityBaseFieldOverride extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityId(Row $row) {
    $entity_type = $row->getDestinationProperty('entity_type');
    $bundle = $row->getDestinationProperty('bundle');
    $field_name = $row->getDestinationProperty('field_name');
    return "$entity_type.$bundle.$field_name";
  }

}
