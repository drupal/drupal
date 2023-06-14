<?php

namespace Drupal\Core\Session;

/**
 * Defines a permission checker interface.
 *
 * This service checks if a role has a permission. It can be swapped out or
 * decorated to allow for more complex logic. If you do so, ensure that you
 * provide ample automated tests so your site remains secure.
 *
 * @ingroup user_api
 */
interface PermissionCheckerInterface {

  /**
   * Checks whether an account has a permission.
   *
   * @param string $permission
   *   The name of the permission to check for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check the permissions.
   *
   * @return bool
   *   Whether the account has the permission.
   */
  public function hasPermission(string $permission, AccountInterface $account): bool;

}
