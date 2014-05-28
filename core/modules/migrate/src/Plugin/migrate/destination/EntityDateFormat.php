<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityDateFormat.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityInterface;

/**
 * @MigrateDestination(
 *   id = "entity:date_format"
 * )
 */
class EntityDateFormat extends EntityConfigBase {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\system\DateFormatInterface $entity
   *   The date entity.
   */
  protected function updateEntityProperty(EntityInterface $entity, array $parents, $value) {
    if ($parents[0] == 'pattern') {
      $entity->setPattern($value, $parents[1]);
    }
    else {
      parent::updateEntityProperty($entity, $parents, $value);
    }
  }

}
