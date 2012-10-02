<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\HttpMethodMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * This class filters routes based on their HTTP Method.
 */
class HttpMethodMatcher extends PartialMatcher {

  /**
   * Matches a request against multiple routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request object against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request) {
    $possible_methods = array();

    $method = $request->getMethod();

    $collection = new RouteCollection();

    foreach ($this->routes->all() as $name => $route) {
      // _method could be a |-delimited list of allowed methods, or null. If
      // null, we accept any method.
      $allowed_methods = array_filter(explode('|', strtoupper($route->getRequirement('_method'))));
      if (empty($allowed_methods) || in_array($method, $allowed_methods)) {
        $collection->add($name, $route);
      }
      else {
        // Build a list of methods that would have matched. Note that we only
        // need to do this if a route doesn't match, because if even one route
        // passes then we'll never throw the exception that needs this array.
        $possible_methods += $allowed_methods;
      }
    }

    if (!count($collection->all())) {
      throw new MethodNotAllowedException(array_unique($possible_methods));
    }

    return $collection;
  }

}

