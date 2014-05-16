<?php

/**
 * @file
 * Contains \Drupal\router_test\Access\DefinedTestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker similar to DefaultAccessCheck
 */
class DefinedTestAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route) {
    if ($route->getRequirement('_test_access') === 'TRUE') {
      return static::ALLOW;
    }
    elseif ($route->getRequirement('_test_access') === 'FALSE') {
      return static::KILL;
    }
    else {
      return static::DENY;
    }
  }

}
