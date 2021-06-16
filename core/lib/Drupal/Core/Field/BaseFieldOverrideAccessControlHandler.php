<?php

namespace Drupal\Core\Field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides en entity access control handler for base field override entity.
 */
class BaseFieldOverrideAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    return $access->orIf(AccessResult::allowedIfHasPermission($account, 'administer ' . $entity->getTargetEntityTypeId() . ' fields'));
  }

}
