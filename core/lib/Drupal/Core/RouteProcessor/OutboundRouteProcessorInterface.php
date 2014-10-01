<?php

/**
 * @file
 * Contains \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface.
 */

namespace Drupal\Core\RouteProcessor;

use Symfony\Component\Routing\Route;

/**
 * Defines an interface for classes that process the outbound route.
 */
interface OutboundRouteProcessorInterface {

  /**
   * Processes the outbound route.
   *
   * @param string $route_name
   *   The route name.
   * @param \Symfony\Component\Routing\Route $route
   *   The outbound route to process.
   * @param array $parameters
   *   An array of parameters to be passed to the route compiler. Passed by
   *   reference.
   *
   * @return
   *   The processed path.
   */
  public function processOutbound($route_name, Route $route, array &$parameters);

}
