<?php

/**
 * @file
 * Contains \Drupal\user\UserAccessController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines the access controller for the user entity type.
 */
class UserAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    switch ($operation) {
      case 'view':
        return $this->viewAccess($entity, $langcode, $account);
        break;

      case 'create':
        return user_access('administer users', $account);
        break;

      case 'update':
        // Users can always edit their own account. Users with the 'administer
        // users' permission can edit any account except the anonymous account.
        return (($account->uid == $entity->uid) || user_access('administer users', $account)) && $entity->uid > 0;
        break;

      case 'delete':
        // Users with 'cancel account' permission can cancel their own account,
        // users with 'administer users' permission can cancel any account
        // except the anonymous account.
        return ((($account->uid == $entity->uid) && user_access('cancel account', $account)) || user_access('administer users', $account)) && $entity->uid > 0;
        break;
    }
  }

  /**
   * Check view access.
   *
   * See EntityAccessControllerInterface::view() for parameters.
   */
  protected function viewAccess(EntityInterface $entity, $langcode, User $account) {
    // Never allow access to view the anonymous user account.
    if ($entity->uid) {
      // Admins can view all, users can view own profiles at all times.
      if ($account->uid == $entity->uid || user_access('administer users', $account)) {
        return TRUE;
      }
      elseif (user_access('access user profiles', $account)) {
        // Only allow view access if the account is active.
        return $entity->status;
      }
    }
    return FALSE;
  }

}
