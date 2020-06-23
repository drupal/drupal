<?php

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\RouteProviderInterface as RouteProviderBaseInterface;

/**
 * Extends the router provider interface
 *
 * @see \Symfony\Cmf\Component\Routing
 */
interface RouteProviderInterface extends RouteProviderBaseInterface {

  /**
   * Get all routes which match a certain pattern.
   *
   * @param string $pattern
   *   The route pattern to search for (contains {} as placeholders).
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   Returns a route collection of matching routes. The collection may be
   *   empty and will be sorted from highest to lowest fit (match of path parts)
   *   and then in ascending order by route name for routes with the same fit.
   */
  public function getRoutesByPattern($pattern);

  /**
   * Returns all the routes on the system.
   *
   * Usage of this method is discouraged for performance reasons. If possible,
   * use RouteProviderInterface::getRoutesByNames() or
   * RouteProviderInterface::getRoutesByPattern() instead.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An iterator of routes keyed by route name.
   */
  public function getAllRoutes();

  /**
   * Resets the route provider object.
   */
  public function reset();

}
