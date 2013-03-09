<?php
/**
 * @file
 * Contains Drupal\Core\Access\AccessManager.
 */

namespace Drupal\Core\Access;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Attaches access check services to routes and runs them on request.
 */
class AccessManager extends ContainerAware {

  /**
   * Array of registered access check service ids.
   *
   * @var array
   */
  protected $checkIds;

  /**
   * Array of access check objects keyed by service id.
   *
   * @var array
   */
  protected $checks;

  /**
   * Registers a new AccessCheck by service ID.
   *
   * @param string $service_id
   *   The ID of the service in the Container that provides a check.
   */
  public function addCheckService($service_id) {
    $this->checkIds[] = $service_id;
  }

  /**
   * For each route, saves a list of applicable access checks to the route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $routes
   *   A collection of routes to apply checks to.
   */
  public function setChecks(RouteCollection $routes) {
    foreach ($routes as $route) {
      $checks = $this->applies($route);
      if (!empty($checks)) {
        $route->setOption('_access_checks', $checks);
      }
    }
  }

  /**
   * Determine which registered access checks apply to a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to get list of access checks for.
   *
   * @return array
   *   An array of service ids for the access checks that apply to passed
   *   route.
   */
  protected function applies(Route $route) {
    $checks = array();

    foreach ($this->checkIds as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      if ($this->checks[$service_id]->applies($route)) {
        $checks[] = $service_id;
      }
    }

    return $checks;
  }

  /**
   * Checks a route against applicable access check services.
   *
   * Determines whether the route is accessible or not.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Commponent\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If any access check denies access or none explicitly approve.
   */
  public function check(Route $route, Request $request) {
    $access = FALSE;
    $checks = $route->getOption('_access_checks') ?: array();

    // No checks == deny by default.
    foreach ($checks as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      $service_access = $this->checks[$service_id]->access($route, $request);
      if ($service_access === FALSE) {
        // A check has denied access, no need to continue checking.
        $access = FALSE;
        break;
      }
      elseif ($service_access === TRUE) {
        // A check has explicitly granted access, so we need to remember that.
        $access = TRUE;
      }
    }

    return $access;
  }

  /**
   * Lazy-loads access check services.
   *
   * @param string $service_id
   *   The service id of the access check service to load.
   */
  protected function loadCheck($service_id) {
    if (!in_array($service_id, $this->checkIds)) {
      throw new \InvalidArgumentException(sprintf('No check has been registered for %s', $service_id));
    }

    $this->checks[$service_id] = $this->container->get($service_id);
  }

}
