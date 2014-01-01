<?php

/**
 * @file
 * Contains Drupal\tracker\Access\ViewOwnTrackerAccessCheck.
 */

namespace Drupal\tracker\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user tracker routes.
 */
class ViewOwnTrackerAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // The user object from the User ID in the path.
    $user = $request->attributes->get('user');
    return ($user && $account->isAuthenticated() && ($user->id() == $account->id())) ? static::ALLOW : static::DENY;
  }
}

