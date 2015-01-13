<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\RouteFilterInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface as BaseRouteFilterInterface;
use Symfony\Component\Routing\Route;

/**
 * A route filter service to filter down the collection of route instances.
 */
interface RouteFilterInterface extends BaseRouteFilterInterface {

  /**
   * Determines if the route filter applies to the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *  The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the check applies to the passed route, FALSE otherwise.
   */
  public function applies(Route $route);

}
