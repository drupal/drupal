<?php

namespace Drupal\Core\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads access checkers from the container.
 */
class CheckProvider implements CheckProviderInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Array of registered access check service ids.
   *
   * @var array
   */
  protected $checkIds = [];

  /**
   * Array of access check objects keyed by service id.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface[]
   */
  protected $checks;

  /**
   * Array of access check method names keyed by service ID.
   *
   * @var array
   */
  protected $checkMethods = [];

  /**
   * Array of access checks which only will be run on the incoming request.
   */
  protected $checksNeedsRequest = [];

  /**
   * An array to map static requirement keys to service IDs.
   *
   * @var array
   */
  protected $staticRequirementMap;

  /**
   * An array to map dynamic requirement keys to service IDs.
   *
   * @var array
   */
  protected $dynamicRequirementMap;

  /**
   * {@inheritdoc}
   */
  public function addCheckService($service_id, $service_method, array $applies_checks = [], $needs_incoming_request = FALSE) {
    $this->checkIds[] = $service_id;
    $this->checkMethods[$service_id] = $service_method;
    if ($needs_incoming_request) {
      $this->checksNeedsRequest[$service_id] = $service_id;
    }
    foreach ($applies_checks as $applies_check) {
      $this->staticRequirementMap[$applies_check][] = $service_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChecksNeedRequest() {
    return $this->checksNeedsRequest;
  }

  /**
   * {@inheritdoc}
   */
  public function setChecks(RouteCollection $routes) {
    $this->loadDynamicRequirementMap();
    foreach ($routes as $route) {
      if ($checks = $this->applies($route)) {
        $route->setOption('_access_checks', $checks);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadCheck($service_id) {
    if (empty($this->checks[$service_id])) {
      if (!in_array($service_id, $this->checkIds)) {
        throw new \InvalidArgumentException(sprintf('No check has been registered for %s', $service_id));
      }

      $check = $this->container->get($service_id);

      if (!($check instanceof AccessInterface)) {
        throw new AccessException('All access checks must implement AccessInterface.');
      }
      if (!is_callable([$check, $this->checkMethods[$service_id]])) {
        throw new AccessException(sprintf('Access check method %s in service %s must be callable.', $this->checkMethods[$service_id], $service_id));
      }

      $this->checks[$service_id] = $check;
    }
    return [$this->checks[$service_id], $this->checkMethods[$service_id]];
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
    $checks = [];

    // Iterate through map requirements from appliesTo() on access checkers.
    // Only iterate through all checkIds if this is not used.
    foreach ($route->getRequirements() as $key => $value) {
      if (isset($this->staticRequirementMap[$key])) {
        foreach ($this->staticRequirementMap[$key] as $service_id) {
          $this->loadCheck($service_id);
          $checks[] = $service_id;
        }
      }
    }
    // Finally, see if any dynamic access checkers apply.
    foreach ($this->dynamicRequirementMap as $service_id) {
      $this->loadCheck($service_id);
      if ($this->checks[$service_id]->applies($route)) {
        $checks[] = $service_id;
      }
    }

    return $checks;
  }

  /**
   * Compiles a mapping of requirement keys to access checker service IDs.
   */
  protected function loadDynamicRequirementMap() {
    if (!isset($this->dynamicRequirementMap)) {
      $this->dynamicRequirementMap = $this->container->getParameter('dynamic_access_check_services');
    }
  }

}
