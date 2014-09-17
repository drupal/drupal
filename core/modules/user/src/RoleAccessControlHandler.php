<?php

/**
 * @file
 * Contains \Drupal\user\RoleAccessControlHandler.
 */

namespace Drupal\user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the user role entity type.
 *
 * @see \Drupal\user\Entity\Role
 */
class RoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        if ($entity->id() == DRUPAL_ANONYMOUS_RID || $entity->id() == DRUPAL_AUTHENTICATED_RID) {
          return AccessResult::forbidden();
        }

      default:
        return parent::checkAccess($entity, $operation, $langcode, $account);
    }
  }

}
