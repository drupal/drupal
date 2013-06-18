<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteProviderInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\RouteProviderInterface as RouteProviderBaseInterface;
use Symfony\Component\Routing\RouteCollection;

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
   *   Returns a route collection of matching routes.
   */
  public function getRoutesByPattern($pattern);

}
