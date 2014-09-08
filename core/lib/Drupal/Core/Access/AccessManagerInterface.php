<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessManagerInterface.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for attaching and running access check services.
 */
interface AccessManagerInterface {

  /**
   * All access checkers have to return AccessInterface::ALLOW.
   *
   * self::ACCESS_MODE_ALL is the default behavior.
   *
   * @see \Drupal\Core\Access\AccessInterface::ALLOW
   */
  const ACCESS_MODE_ALL = 'ALL';

  /**
   * At least one access checker has to return AccessInterface::ALLOW
   * and none should return AccessInterface::KILL.
   *
   * @see \Drupal\Core\Access\AccessInterface::ALLOW
   * @see \Drupal\Core\Access\AccessInterface::KILL
   */
  const ACCESS_MODE_ANY = 'ANY';

  /**
   * Checks a named route with parameters against applicable access check services.
   *
   * Determines whether the route is accessible or not.
   *
   * @param string $route_name
   *   The route to check access to.
   * @param array $parameters
   *   Optional array of values to substitute into the route path patern.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   * @param \Symfony\Component\HttpFoundation\Request $route_request
   *   Optional incoming request object. If not provided, one will be built
   *   using the route information and the current request from the container.
   *
   * @return bool
   *   Returns TRUE if the user has access to the route, otherwise FALSE.
   */
  public function checkNamedRoute($route_name, array $parameters = array(), AccountInterface $account = NULL, Request $route_request = NULL);

  /**
   * For each route, saves a list of applicable access checks to the route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   A collection of routes to apply checks to.
   */
  public function setChecks(RouteCollection $routes);

  /**
   * Registers a new AccessCheck by service ID.
   *
   * @param string $service_id
   *   The ID of the service in the Container that provides a check.
   * @param string $service_method
   *   The method to invoke on the service object for performing the check.
   * @param array $applies_checks
   *   (optional) An array of route requirement keys the checker service applies
   *   to.
   */
  public function addCheckService($service_id, $service_method, array $applies_checks = array());

  /**
   * Checks a route against applicable access check services.
   *
   * Determines whether the route is accessible or not.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   *
   * @return bool
   *   Returns TRUE if the user has access to the route, otherwise FALSE.
   */
  public function check(Route $route, Request $request, AccountInterface $account = NULL);

}
