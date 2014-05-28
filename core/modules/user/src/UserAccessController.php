<?php

/**
 * @file
 * Contains \Drupal\user\UserAccessController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the user entity type.
 */
class UserAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return $this->viewAccess($entity, $langcode, $account);
        break;

      case 'update':
        // Users can always edit their own account. Users with the 'administer
        // users' permission can edit any account except the anonymous account.
        return (($account->id() == $entity->id()) || $account->hasPermission('administer users')) && $entity->id() > 0;
        break;

      case 'delete':
        // Users with 'cancel account' permission can cancel their own account,
        // users with 'administer users' permission can cancel any account
        // except the anonymous account.
        return ((($account->id() == $entity->id()) && $account->hasPermission('cancel account')) || $account->hasPermission('administer users')) && $entity->id() > 0;
        break;
    }
  }

  /**
   * Check view access.
   *
   * See EntityAccessControllerInterface::view() for parameters.
   */
  protected function viewAccess(EntityInterface $entity, $langcode, AccountInterface $account) {
    // Never allow access to view the anonymous user account.
    if ($entity->id()) {
      // Admins can view all, users can view own profiles at all times.
      if ($account->id() == $entity->id() || $account->hasPermission('administer users')) {
        return TRUE;
      }
      elseif ($account->hasPermission('access user profiles')) {
        // Only allow view access if the account is active.
        return $entity->status->value;
      }
    }
    return FALSE;
  }

}
