<?php

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Routing\InitialMatcherInterface;

/**
 * Provides a mock path matcher.
 */
class MockPathMatcher implements InitialMatcherInterface {

  /**
   * Routes to be matched.
   *
   * @var Symfony\Component\Routing\RouteCollection
   */
  protected $routes;

  /**
   * Construct the matcher given the route collection.
   *
   * @param Symfony\Component\Routing\RouteCollection $routes
   *   The routes being matched.
   */
  public function __construct(RouteCollection $routes) {
    $this->routes = $routes;
  }

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
    // For now for testing we'll just do a straight string match.

    $path = $request->getPathInfo();

    $return = new RouteCollection();

    foreach ($this->routes as $name => $route) {
      if ($route->getPattern() == $path) {
        $return->add($name, $route);
      }
    }

    return $return;
  }


}
