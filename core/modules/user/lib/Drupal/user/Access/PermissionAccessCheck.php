<?php

/**
 * @file
 * Contains Drupal\user\Access\PermissionAccessCheck.
 */

namespace Drupal\user\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on permissions defined via hook_permission().
 */
class PermissionAccessCheck implements AccessInterface {

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $permission = $route->getRequirement('_permission');
    // If the access check fails, return NULL to give other checks a chance.
    return $account->hasPermission($permission) ? static::ALLOW : static::DENY;
  }
}
