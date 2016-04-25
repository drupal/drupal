<?php

namespace Drupal\Core\Session;

/**
 * Defines the user permissions hash generator interface.
 */
interface PermissionsHashGeneratorInterface {

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
