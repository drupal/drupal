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
class DefaultAccessCheck implements StaticAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
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
