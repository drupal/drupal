<?php

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
   * @param Request $request
   *   A Request object against which to match.
   *
   * @return RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request) {

    $method = $request->getMethod();

    $collection = new RouteCollection();

    foreach ($this->routes->all() as $name => $route) {
      $allowed_methods = $route->getRequirement('_method');
      if ($allowed_methods === NULL || in_array($method, explode('|', strtoupper($allowed_methods)))) {
        $collection->add($name, $route);
      }
    }
    return $collection;
  }

}

