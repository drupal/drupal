<?php

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Routing\FinalMatcherInterface;


/**
 * Mock final matcher for testing.
 *
 * This class simply matches the first remaining route.
 */
class MockFinalMatcher implements FinalMatcherInterface {
  protected $routes;

  public function setCollection(RouteCollection $collection) {
    $this->routes = $collection;

    return $this;
  }


  public function matchRequest(Request $request) {
    // For testing purposes, just return whatever the first route is.
    foreach ($this->routes as $name => $route) {
      return array_merge($this->mergeDefaults(array(), $route->getDefaults()), array('_route' => $name));
    }
  }

  /**
   * Get merged default parameters.
   *
   * @param array $params
   *  The parameters
   * @param array $defaults
   *   The defaults
   *
   * @return array
   *   Merged default parameters
   */
  protected function mergeDefaults($params, $defaults) {
    $parameters = $defaults;
    foreach ($params as $key => $value) {
      if (!is_int($key)) {
        $parameters[$key] = $value;
      }
    }

    return $parameters;
  }

}

