<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityRevision.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity_revision",
 *   derivative = "Drupal\migrate\Plugin\Derivative\MigrateEntityRevision"
 * )
 */
class EntityRevision extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    // Remove entity_revision:
    return substr($plugin_id, 16);
  }

  /**
   * Get the entity.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity or false if it can not be created.
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $revision_id = $old_destination_id_values ? reset($old_destination_id_values) : $row->getDestinationProperty($this->getKey('revision'));
    if (!empty($revision_id) && ($entity = $this->storage->loadRevision($revision_id))) {
      $entity->setNewRevision(FALSE);
    }
    else {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      $entity = $this->storage->load($entity_id);
      $entity->enforceIsNew(FALSE);
      $entity->setNewRevision(TRUE);
    }
    $this->updateEntity($entity, $row);
    $entity->isDefaultRevision(FALSE);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = array()) {
    $entity->save();
    return array($entity->getRevisionId());
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if ($key = $this->getKey('revision')) {
      $ids[$key]['type'] = 'integer';
      return $ids;
    }
    throw new MigrateException('This entity type does not support revisions.');
  }

}
