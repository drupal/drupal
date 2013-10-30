<?php

/**
 * @file
 * Contains \Drupal\router_test\Access\DefinedTestAccessCheck.
 */

namespace Drupal\router_test\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker similar to DefaultAccessCheck
 */
class DefinedTestAccessCheck implements AccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return array_key_exists('_test_access', $route->getRequirements());
  }

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
