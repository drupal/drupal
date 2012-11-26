<?php

/**
 * @file
 * Contains Drupal\user\Access\RegisterAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessCheckInterface {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    return array_key_exists('_access_user_register', $route->getRequirements());
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    return user_is_anonymous() && (config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY);
  }
}
