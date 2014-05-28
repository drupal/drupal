<?php

/**
 * @file
 * Contains Drupal\user\PermissionsHashInterface.
 */

namespace Drupal\user;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the user permissions hash interface.
 */
interface PermissionsHashInterface {

  /**
   * Generates a hash that uniquely identifies a user's permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to get the permissions hash.
   *
   * @return string
   *   A permissions hash.
   */
  public function generate(AccountInterface $account);

}
