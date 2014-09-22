<?php

/**
 * @file
 * Contains \Drupal\user\UserAccessControlHandler.
 */

namespace Drupal\user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the user entity type.
 *
 * @see \Drupal\user\Entity\User
 */
class UserAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\user\UserInterface $entity*/

    // The anonymous user's profile can neither be viewed, updated nor deleted.
    if ($entity->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // Administrators can view/update/delete all user profiles.
    if ($account->hasPermission('administer users')) {
      return AccessResult::allowed()->cachePerRole();
    }

    switch ($operation) {
      case 'view':
        // Only allow view access if the account is active.
        if ($account->hasPermission('access user profiles') && $entity->isActive()) {
          return AccessResult::allowed()->cachePerRole()->cacheUntilEntityChanges($entity);
        }
        // Users can view own profiles at all times.
        else if ($account->id() == $entity->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        break;

      case 'update':
        // Users can always edit their own account.
        return AccessResult::allowedIf($account->id() == $entity->id())->cachePerUser();

      case 'delete':
        // Users with 'cancel account' permission can cancel their own account.
        return AccessResult::allowedIf($account->id() == $entity->id() && $account->hasPermission('cancel account'))->cachePerRole()->cachePerUser();
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
