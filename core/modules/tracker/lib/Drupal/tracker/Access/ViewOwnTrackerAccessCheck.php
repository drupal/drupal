<?php

/**
 * @file
 * Contains Drupal\tracker\Access\ViewOwnTrackerAccessCheck.
 */

namespace Drupal\tracker\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Access check for user tracker routes.
 */
class ViewOwnTrackerAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\user\UserInterface $user
   *   The user whose tracker page is being accessed.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account, UserInterface $user) {
    return ($user && $account->isAuthenticated() && ($user->id() == $account->id())) ? static::ALLOW : static::DENY;
  }
}
