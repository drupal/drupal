<?php

namespace Drupal\router_test\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker similar to DefaultAccessCheck.
 */
class DefinedTestAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route) {
    if ($route->getRequirement('_test_access') === 'TRUE') {
      return AccessResult::allowed();
    }
    elseif ($route->getRequirement('_test_access') === 'FALSE') {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::neutral();
    }
  }

}
