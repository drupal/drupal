<?php

/**
 * @file
 * Contains \Drupal\user\Access\LoginStatusCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    return $account->isAuthenticated() ? static::ALLOW : static::DENY;
  }

}
