<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplayBase.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;

abstract class ComponentEntityDisplayBase extends DestinationBase {

  const MODE_NAME = '';

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $values = array();
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);
    if (!$row->getDestinationProperty('hidden')) {
      $entity
        ->setComponent($values['field_name'], $row->getDestinationProperty('options') ?: array())
        ->save();
    }
    else {
      $entity->removeComponent($values['field_name']);
    }
    return array_values($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids[static::MODE_NAME]['type'] = 'string';
    $ids['field_name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // This is intentionally left empty.
  }

  /**
   * Get the entity.
   *
   * @param string $entity_type
   *   The entity type to retrieve.
   * @param string $bundle
   *   The entity bundle.
   * @param string $mode
   *   The display mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   The entity display object.
   */
  protected abstract function getEntity($entity_type, $bundle, $mode);

}
