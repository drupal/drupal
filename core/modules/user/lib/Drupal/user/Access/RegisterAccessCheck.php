<?php

/**
 * @file
 * Contains Drupal\user\Access\RegisterAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessInterface {

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return ($request->attributes->get('_menu_admin') || $account->isAnonymous()) && (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) ? static::ALLOW : static::DENY;
  }
}
