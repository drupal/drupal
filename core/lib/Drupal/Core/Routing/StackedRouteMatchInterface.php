<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\StackedRouteMatchInterface.
 */

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
   */
  public function getCurrentRouteMatch();

  /**
   * Gets the master route match..
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  public function getMasterRouteMatch();

  /**
   * Returns the parent route match of the current.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|NULL
   *   The parent route match or NULL, if it the master route match.
   */
  public function getParentRouteMatch();

  /**
   * Returns a route match from a given request, if possible.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *   The request.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface|NULL
   *   THe matching route match, or NULL if there is no matching one.
   */
  public function getRouteMatchFromRequest(Request $request);

}
