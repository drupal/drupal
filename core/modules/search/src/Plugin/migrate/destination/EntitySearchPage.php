<?php

/**
 * @file
 * Contains \Drupal\search\Plugin\migrate\destination\EntitySearchPage.
 */

namespace Drupal\search\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:search_page"
 * )
 */
class EntitySearchPage extends EntityConfigBase {

  /**
   * Updates the entity with the contents of a row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The search page entity.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    $entity->setPlugin($row->getDestinationProperty('plugin'));
    $entity->getPlugin()->setConfiguration($row->getDestinationProperty('configuration'));
  }

}
