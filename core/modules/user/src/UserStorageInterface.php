<?php

/**
 * @file
 * Contains \Drupal\user\UserStorageInterface.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for user entity storage classes.
 */
interface UserStorageInterface extends EntityStorageInterface{

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

  /**
   * Update the last access timestamp of the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param int $timestamp
   *   The last access timestamp.
   */
  public function updateLastAccessTimestamp(AccountInterface $account, $timestamp);

}
