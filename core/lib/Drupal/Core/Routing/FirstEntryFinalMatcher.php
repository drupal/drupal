<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\FirstEntryFinalMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Final matcher that simply returns the first item in the remaining routes.
 *
 * This class simply matches the first remaining route.
 */
class FirstEntryFinalMatcher implements FinalMatcherInterface {

  /**
   * The RouteCollection this matcher should match against.
   *
   * @var RouteCollection
   */
  protected $routes;

  /**
   * Sets the route collection this matcher should use.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection against which to match.
   *
   * @return \Drupal\Core\Routing\FinalMatcherInterface
   *   The current matcher.
   */
  public function setCollection(RouteCollection $collection) {
    $this->routes = $collection;

    return $this;
  }

  /**
   * Implements Drupal\Core\Routing\FinalMatcherInterface::matchRequest().
   */
  public function matchRequest(Request $request) {
    // Return whatever the first route in the collection is.
    foreach ($this->routes as $name => $route) {
      $path = '/' . $request->attributes->get('system_path');

      $route->setOption('compiler_class', '\Drupal\Core\Routing\RouteCompiler');
      $compiled = $route->compile();

      preg_match($compiled->getRegex(), $path, $matches);

      $route->setOption('_name', $name);
      return array_merge($this->mergeDefaults($matches, $route->getDefaults()), array('_route' => $route));
    }
  }

  /**
   * Get merged default parameters.
   *
   * @param array $params
   *  The parameters.
   * @param array $defaults
   *   The defaults.
   *
   * @return array
   *   Merged default parameters.
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
