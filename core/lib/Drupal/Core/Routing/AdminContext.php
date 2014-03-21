<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\AdminContext.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides a helper class to determine whether the route is an admin one.
 */
class AdminContext {

  /**
   * The route object.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * Sets the request object to use.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request) {
    $this->route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
  }

  /**
   * Determines whether the active route is an admin one.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   (optional) The route to determine whether it is an admin one. Per default
   *   this falls back to the route object on the active request.
   *
   * @return bool
   *   Returns TRUE if the route is an admin one, otherwise FALSE.
   */
  public function isAdminRoute(Route $route = NULL) {
    if (!$route) {
      $route = $this->route;
      if (!$route) {
        return FALSE;
      }
    }
    return (bool) $route->getOption('_admin_route');
  }

}
