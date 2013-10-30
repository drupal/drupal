<?php

/**
 * @file
 * Contains \Drupal\user\Access\LoginStatusCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_user_is_logged_in');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $account->isAuthenticated() ? static::ALLOW : static::DENY;
  }

}
