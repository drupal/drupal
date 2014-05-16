<?php

/**
 * @file
 * Contains \Drupal\user\Access\LoginStatusCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Request $request, AccountInterface $account) {
    return ($request->attributes->get('_menu_admin') || $account->isAuthenticated()) ? static::ALLOW : static::DENY;
  }

}
