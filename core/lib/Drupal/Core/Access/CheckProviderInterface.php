<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CheckProviderInterface.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\RouteCollection;

/**
 * Provides the available access checkers by service IDs.
 *
 * Access checker services are added by ::addCheckService calls and are loaded
 * by ::loadCheck.
 *
 * The checker provider service and the actual checking is separated in order
 * to not require the full access manager on route build time.
 */
interface CheckProviderInterface {


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
   * @param bool $needs_incoming_request
   *   (optional) True if access-check method only acts on an incoming request.
   */
  public function addCheckService($service_id, $service_method, array $applies_checks = array(), $needs_incoming_request = FALSE);

  /**
   * Lazy-loads access check services.
   *
   * @param string $service_id
   *   The service id of the access check service to load.
   *
   * @return callable
   *   A callable access check.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the service hasn't been registered in addCheckService().
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the service doesn't implement the required interface.
   */
  public function loadCheck($service_id);

  /**
   * A list of checks that needs the request.
   *
   * @return array
   */
  public function getChecksNeedRequest();
}
