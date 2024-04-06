<?php

namespace Drupal\system\Plugin\migrate\destination;

use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;

/**
 * Migration destination for date format entity.
 */
#[MigrateDestination('entity:date_format')]
class EntityDateFormat extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  protected function updateEntityProperty(EntityInterface $entity, array $parents, $value) {
    assert($entity instanceof DateFormatInterface);
    if ($parents[0] == 'pattern') {
      $entity->setPattern($value);
    }
    else {
      parent::updateEntityProperty($entity, $parents, $value);
    }
  }

}
