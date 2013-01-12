<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\MockRouteProvider.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;

/**
 * Easily configurable mock route provider.
 */
class MockRouteProvider implements RouteProviderInterface {

  /**
   *
   * @var RouteCollection
   */
  protected $routes;

  public function __construct(RouteCollection $routes) {
    $this->routes = $routes;
  }

  public function getRouteCollectionForRequest(Request $request) {

  }

  public function getRouteByName($name, $parameters = array()) {
    $routes = $this->getRoutesByNames(array($name), $parameters);
    if (empty($routes)) {
      throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
    }

    return reset($routes);
  }

  public function getRoutesByNames($names, $parameters = array()) {
    $routes = array();
    foreach ($names as $name) {
      $routes[] = $this->routes->get($name);
    }

    return $routes;
  }

}
