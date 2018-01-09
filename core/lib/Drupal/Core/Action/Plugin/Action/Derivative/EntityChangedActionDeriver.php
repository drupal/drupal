<?php

namespace Drupal\Core\Action\Plugin\Action\Derivative;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an action deriver that finds entity types of EntityChangedInterface.
 *
 * @see \Drupal\Core\Action\Plugin\Action\SaveAction
 */
class EntityChangedActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements(EntityChangedInterface::class);
  }

}
