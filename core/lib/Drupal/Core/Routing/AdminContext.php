<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\AdminContext.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Provides a helper class to determine whether the route is an admin one.
 */
class AdminContext {

  /**
   * The request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct a new admin context helper instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to determine the current request.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
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
      $route = $this->getRouteFromRequest();
      if (!$route) {
        return FALSE;
      }
    }
    return (bool) $route->getOption('_admin_route');
  }

  /**
   * Extract the route object from the request if one is available.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route object extracted from the current request.
   */
  protected function getRouteFromRequest() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    }
  }

}
