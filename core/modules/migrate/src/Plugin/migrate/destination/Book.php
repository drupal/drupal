<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\Book.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "book",
 *   provider = "book"
 * )
 */
class Book extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    $entity->book = $row->getDestinationProperty('book');
  }

}
