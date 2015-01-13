<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\Enhancer\RouteEnhancerInterface.
 */

namespace Drupal\Core\Routing\Enhancer;

use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface as BaseRouteEnhancerInterface;
use Symfony\Component\Routing\Route;

/**
 * A route enhance service to determine route enhance rules.
 */
interface RouteEnhancerInterface extends BaseRouteEnhancerInterface {

  /**
   * Declares if the route enhancer applies to the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *  The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the check applies to the passed route, False otherwise.
   */
  public function applies(Route $route);

}
