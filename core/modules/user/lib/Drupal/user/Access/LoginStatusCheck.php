<?php

/**
 * @file
 * Contains \Drupal\user\Access\LoginStatusCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LoginStatusCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return ($request->attributes->get('_menu_admin') || $account->isAuthenticated()) ? static::ALLOW : static::DENY;
  }

}
