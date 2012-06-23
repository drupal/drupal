<?php

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Routing\InitialMatcherInterface;

/**
 * Description of MockPathMatcher
 *
 * @author crell
 */
class MockPathMatcher implements InitialMatcherInterface {

  protected $routes;

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
