<?php

/**
 * @file
 * Contains Drupal\Core\Access\DefaultAccessCheck.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Allows access to routes to be controlled by an '_access' boolean parameter.
 */
class DefaultAccessCheck implements RoutingAccessInterface {

  /**
   * Checks access to the route based on the _access parameter.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route) {
    if ($route->getRequirement('_access') === 'TRUE') {
      return static::ALLOW;
    }
    elseif ($route->getRequirement('_access') === 'FALSE') {
      return static::KILL;
    }
    else {
      return static::DENY;
    }
  }

}
