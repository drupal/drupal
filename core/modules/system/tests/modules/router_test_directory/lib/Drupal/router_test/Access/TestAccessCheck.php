<?php

/**
 * @file
 * Contains Drupal\router_test\Access\TestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for test routes.
 */
class TestAccessCheck implements AccessCheckInterface {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    return array_key_exists('_access_router_test', $route->getRequirements());
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return static::DENY;
  }
}
