<?php

/**
 * @file
 * Contains Drupal\user\Access\RegisterAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessInterface {

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
    return ($request->attributes->get('_menu_admin') || $account->isAnonymous()) && (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) ? static::ALLOW : static::DENY;
  }
}
