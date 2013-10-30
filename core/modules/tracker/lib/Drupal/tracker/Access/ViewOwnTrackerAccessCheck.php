<?php

/**
 * @file
 * Contains Drupal\tracker\Access\ViewOwnTrackerAccessCheck.
 */

namespace Drupal\tracker\Access;

use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for user tracker routes.
 */
class ViewOwnTrackerAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_tracker_own_information');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // The user object from the User ID in the path.
    $user = $request->attributes->get('user');
    return $user && $account->isAuthenticated() && ($user->id() == $account->id());
  }
}

