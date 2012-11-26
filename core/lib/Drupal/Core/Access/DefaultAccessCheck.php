<?php

/**
 * @file
 * Contains Drupal\Core\Access\DefaultAccessCheck.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class DefaultAccessCheck implements AccessCheckInterface {

  /**
   * Implements AccessCheckInterface::applies().
   */
  public function applies(Route $route) {
    return array_key_exists('_access', $route->getRequirements());
  }

  /**
   * Implements AccessCheckInterface::access().
   */
  public function access(Route $route, Request $request) {
    return $route->getRequirement('_access');
  }
}
