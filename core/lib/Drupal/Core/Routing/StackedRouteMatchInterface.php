<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for a stack of route matches.
 *
 * This could be for example used on exception pages.
 */
interface StackedRouteMatchInterface extends RouteMatchInterface {

  /**
   * Gets the current route match.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match object for the current route.
   */
  public function getCurrentRouteMatch();

  /**
   * Gets the master route match..
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match object for the master route.
   */
  public function getMasterRouteMatch();

  /**
   * Returns the parent route match of the current.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|null
   *   The parent route match or NULL, if it the master route match.
   */
  public function getParentRouteMatch();

  /**
   * Returns a route match from a given request, if possible.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|null
   *   The matching route match, or NULL if there is no matching one.
   */
  public function getRouteMatchFromRequest(Request $request);

}
