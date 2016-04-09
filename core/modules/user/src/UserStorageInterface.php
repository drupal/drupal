<?php

namespace Drupal\user;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for user entity storage classes.
 */
interface UserStorageInterface extends ContentEntityStorageInterface {

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

  /**
   * Delete role references.
   *
   * @param array $rids
   *   The list of role IDs being deleted. The storage should
   *   remove permission and user references to this role.
   */
  public function deleteRoleReferences(array $rids);

}
