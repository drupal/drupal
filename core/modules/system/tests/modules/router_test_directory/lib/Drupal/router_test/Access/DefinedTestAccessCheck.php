<?php

/**
 * @file
 * Contains \Drupal\router_test\Access\DefinedTestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker similar to DefaultAccessCheck
 */
class DefinedTestAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
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
