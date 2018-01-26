<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Route;

@trigger_error('\Drupal\Core\Routing\Enhancer\RouteFilterInterface is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Instead, you should use \Drupal\Core\Routing\FilterInterface. See https://www.drupal.org/node/2894934', E_USER_DEPRECATED);

/**
 * A route filter service to filter down the collection of route instances.
 *
 * @deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead,
 * you should use \Drupal\Core\Routing\FilterInterface.
 * See https://www.drupal.org/node/2894934
 */
interface RouteFilterInterface extends FilterInterface {

  /**
   * Determines if the route filter applies to the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to consider attaching to.
   *
   * @return bool
   *   TRUE if the check applies to the passed route, FALSE otherwise.
   */
  public function applies(Route $route);

}
