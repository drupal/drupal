<?php

namespace Drupal\Core\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Component\Routing\Route;

@trigger_error('\Drupal\Core\Routing\Enhancer\RouteEnhancerInterface is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Instead, you should use \Drupal\Core\Routing\EnhancerInterface. See https://www.drupal.org/node/2894934', E_USER_DEPRECATED);

/**
 * A route enhance service to determine route enhance rules.
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Instead,
 * you should use \Drupal\Core\Routing\EnhancerInterface.
 * See https://www.drupal.org/node/2894934
 * Part of the deprecation means that applies() is now called on runtime instead
 * of compile time.
 */
interface RouteEnhancerInterface extends EnhancerInterface {

  /**
   * Declares if the route enhancer applies to the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the check applies to the passed route, False otherwise.
   */
  public function applies(Route $route);

}
