<?php

namespace Drupal\Core\Action\Plugin\Action\Derivative;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an action deriver that finds publishable entity types.
 *
 * @see \Drupal\Core\Action\Plugin\Action\PublishAction
 * @see \Drupal\Core\Action\Plugin\Action\UnpublishAction
 */
class EntityPublishedActionDeriver extends EntityActionDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements(EntityPublishedInterface::class);
  }

}
