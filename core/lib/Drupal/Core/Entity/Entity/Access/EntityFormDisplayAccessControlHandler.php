<?php

namespace Drupal\Core\Entity\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an entity access control handler for form displays.
 */
class EntityFormDisplayAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    return parent::checkAccess($entity, $operation, $account)
      ->orIf(AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->getTargetEntityTypeId() . ' form display'));
  }

}
