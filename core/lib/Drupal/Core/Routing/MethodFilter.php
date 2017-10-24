<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters routes based on the HTTP method.
 */
class MethodFilter implements FilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    $method = $request->getMethod();

    $all_supported_methods = [];

    foreach ($collection->all() as $name => $route) {
      $supported_methods = $route->getMethods();

      // A route not restricted to specific methods allows any method. If this
      // is the case, we'll also have at least one route left in the collection,
      // hence we don't need to calculate the set of all supported methods.
      if (empty($supported_methods)) {
        continue;
      }

      // If the GET method is allowed we also need to allow the HEAD method
      // since HEAD is a GET method that doesn't return the body.
      if (in_array('GET', $supported_methods, TRUE)) {
        $supported_methods[] = 'HEAD';
      }

      if (!in_array($method, $supported_methods, TRUE)) {
        $all_supported_methods = array_merge($supported_methods, $all_supported_methods);
        $collection->remove($name);
      }
    }
    if (count($collection)) {
      return $collection;
    }
    throw new MethodNotAllowedException(array_unique($all_supported_methods));
  }

}
