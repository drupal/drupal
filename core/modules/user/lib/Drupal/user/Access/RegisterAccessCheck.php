<?php

/**
 * @file
 * Contains Drupal\user\Access\RegisterAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_user_register');
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return ($account->isAnonymous() && (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY)) ? static::ALLOW : static::DENY;
  }
}
