<?php

/**
 * @file
 * Contains \Drupal\system\MenuAccessControlHandler.
 */

namespace Drupal\system;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the menu entity type.
 *
 * @see \Drupal\system\Entity\Menu
 */
class MenuAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowed();
    }
    // Locked menus could not be deleted.
    elseif ($operation == 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)->cacheUntilEntityChanges($entity);
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
