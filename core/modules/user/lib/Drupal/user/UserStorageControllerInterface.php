<?php

/**
 * @file
 * Contains \Drupal\user\UserStorageControllerInterface.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for user entity controller classes.
 */
interface UserStorageControllerInterface {

  /**
   * Add any roles from the storage to the user.
   *
   * @param array $users
   */
  public function addRoles(array $users);

  /**
   * Save the user's roles.
   *
   * @param \Drupal\user\UserInterface $account
   */
  public function saveRoles(UserInterface $account);

  /**
   * Remove the roles of a user.
   *
   * @param array $uids
   */
  public function deleteUserRoles(array $uids);

  /**
   * Update the last login timestamp of the user.
   *
   * @param \Drupal\user\UserInterface $account
   */
  public function updateLastLoginTimestamp(UserInterface $account);
}
