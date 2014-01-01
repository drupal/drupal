<?php

/**
 * @file
 * Contains Drupal\router_test\Access\TestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for test routes.
 */
class TestAccessCheck implements AccessInterface {

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return static::DENY;
  }
}
