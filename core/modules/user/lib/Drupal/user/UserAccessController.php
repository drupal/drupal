<?php

/**
 * @file
 * Contains \Drupal\user\UserAccessController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControllerInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines the access controller for the user entity type.
 */
class UserAccessController implements EntityAccessControllerInterface {

  /**
   * Implements EntityAccessControllerInterface::viewAccess().
   */
  public function viewAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    $uid = $entity->uid;
    if (!$account) {
      $account = $GLOBALS['user'];
    }

    // Never allow access to view the anonymous user account.
    if ($uid) {
      // Admins can view all, users can view own profiles at all times.
      if ($account->uid == $uid || user_access('administer users', $account)) {
        return TRUE;
      }
      elseif (user_access('access user profiles', $account)) {
        // Only allow view access if the account is active.
        return $entity->status;
      }
    }
    return FALSE;
  }

  /**
   * Implements EntityAccessControllerInterface::createAccess().
   */
  public function createAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    return user_access('administer users', $account);
  }

  /**
   * Implements EntityAccessControllerInterface::updateAccess().
   */
  public function updateAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (!$account) {
      $account = $GLOBALS['user'];
    }
    // Users can always edit their own account. Users with the 'administer
    // users' permission can edit any account except the anonymous account.
    return (($account->uid == $entity->uid) || user_access('administer users', $account)) && $entity->uid > 0;
  }

  /**
   * Implements EntityAccessControllerInterface::deleteAccess().
   */
  public function deleteAccess(EntityInterface $entity, $langcode = LANGUAGE_DEFAULT, User $account = NULL) {
    if (!$account) {
      $account = $GLOBALS['user'];
    }
    // Users with 'cancel account' permission can cancel their own account,
    // users with 'administer users' permission can cancel any account except
    // the anonymous account.
    return ((($account->uid == $entity->uid) && user_access('cancel account', $account)) || user_access('administer users', $account)) && $entity->uid > 0;
  }

}
