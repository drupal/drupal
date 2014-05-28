<?php

/**
 * @file
 * Contains Drupal\user\Access\PermissionAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on permissions defined via hook_permission().
 */
class PermissionAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, AccountInterface $account) {
    $permission = $route->getRequirement('_permission');
    return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
  }
}
