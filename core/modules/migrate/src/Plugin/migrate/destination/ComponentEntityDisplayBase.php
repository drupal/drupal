<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides a destination plugin for migrating entity display components.
 *
 * Display modes provide different presentations for viewing ('view modes') or
 * editing ('form modes') content. This destination plugin is an abstract base
 * class for migrating fields and other components into view and form modes.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay
 */
abstract class ComponentEntityDisplayBase extends DestinationBase {

  const MODE_NAME = '';

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);
    if (!$row->getDestinationProperty('hidden')) {
      $entity->setComponent($values['field_name'], $row->getDestinationProperty('options') ?: []);
    }
    else {
      $entity->removeComponent($values['field_name']);
    }
    $entity->save();
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
   * Gets the entity.
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
  abstract protected function getEntity($entity_type, $bundle, $mode);

}
