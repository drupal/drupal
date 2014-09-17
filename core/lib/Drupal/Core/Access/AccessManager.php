<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessManager.
 */

namespace Drupal\Core\Access;

use Drupal\Core\ParamConverter\ParamConverterManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\ArgumentsResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Attaches access check services to routes and runs them on request.
 *
 * @see \Drupal\Tests\Core\Access\AccessManagerTest
 */
class AccessManager implements ContainerAwareInterface, AccessManagerInterface {

  use ContainerAwareTrait;

  /**
   * Array of registered access check service ids.
   *
   * @var array
   */
  protected $checkIds = array();

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
  protected $checkMethods = array();

  /**
   * Array of access checks which only will be run on the incoming request.
   */
  protected $checkNeedsRequest = array();

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
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The paramconverter manager.
   *
   * @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface
   */
  protected $paramConverterManager;

  /**
   * The access arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface
   */
  protected $argumentsResolverFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a AccessManager instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\ParamConverter\ParamConverterManagerInterface $paramconverter_manager
   *   The param converter manager.
   * @param \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface $arguments_resolver_factory
   *   The access arguments resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(RouteProviderInterface $route_provider, ParamConverterManagerInterface $paramconverter_manager, AccessArgumentsResolverFactoryInterface $arguments_resolver_factory, AccountInterface $current_user) {
    $this->routeProvider = $route_provider;
    $this->paramConverterManager = $paramconverter_manager;
    $this->argumentsResolverFactory = $arguments_resolver_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function addCheckService($service_id, $service_method, array $applies_checks = array(), $needs_incoming_request = FALSE) {
    $this->checkIds[] = $service_id;
    $this->checkMethods[$service_id] = $service_method;
    if ($needs_incoming_request) {
      $this->checkNeedsRequest[$service_id] = $service_id;
    }
    foreach ($applies_checks as $applies_check) {
      $this->staticRequirementMap[$applies_check][] = $service_id;
    }
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

    // Iterate through map requirements from appliesTo() on access checkers.
    // Only iterate through all checkIds if this is not used.
    foreach ($route->getRequirements() as $key => $value) {
      if (isset($this->staticRequirementMap[$key])) {
        foreach ($this->staticRequirementMap[$key] as $service_id) {
          $checks[] = $service_id;
        }
      }
    }
    // Finally, see if any dynamic access checkers apply.
    foreach ($this->dynamicRequirementMap as $service_id) {
      if ($this->checks[$service_id]->applies($route)) {
        $checks[] = $service_id;
      }
    }

    return $checks;
  }

  /**
   * {@inheritdoc}
   */
  public function checkNamedRoute($route_name, array $parameters = array(), AccountInterface $account = NULL, $return_as_object = FALSE) {
    try {
      $route = $this->routeProvider->getRouteByName($route_name, $parameters);

      // ParamConverterManager relies on the route object being available
      // from the parameters array.
      $parameters[RouteObjectInterface::ROUTE_OBJECT] = $route;
      $upcasted_parameters = $this->paramConverterManager->convert($parameters + $route->getDefaults());

      $route_match = new RouteMatch($route_name, $route, $upcasted_parameters, $parameters);
      return $this->check($route_match, $account, NULL, $return_as_object);
    }
    catch (RouteNotFoundException $e) {
      // Cacheable until extensions change.
      $result = AccessResult::forbidden()->addCacheTags(array('extension' => TRUE));
      return $return_as_object ? $result : $result->isAllowed();
    }
    catch (ParamNotConvertedException $e) {
      // Uncacheable because conversion of the parameter may not have been
      // possible due to dynamic circumstances.
      $result = AccessResult::forbidden()->setCacheable(FALSE);
      return $return_as_object ? $result : $result->isAllowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequest(Request $request, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $route_match = RouteMatch::createFromRequest($request);
    return $this->check($route_match, $account, $request, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function check(RouteMatchInterface $route_match, AccountInterface $account = NULL, Request $request = NULL, $return_as_object = FALSE) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }
    $route = $route_match->getRouteObject();
    $checks = $route->getOption('_access_checks') ?: array();
    $conjunction = $route->getOption('_access_mode') ?: static::ACCESS_MODE_ALL;

    // Filter out checks which require the incoming request.
    if (!isset($request)) {
      $checks = array_diff($checks, $this->checkNeedsRequest);
    }

    $result = AccessResult::create();
    if (!empty($checks)) {
      $arguments_resolver = $this->argumentsResolverFactory->getArgumentsResolver($route_match, $account, $request);
      if ($conjunction == static::ACCESS_MODE_ALL) {
        $result = $this->checkAll($checks, $arguments_resolver);
      }
      else {
        $result = $this->checkAny($checks, $arguments_resolver);
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Checks access so that every checker should allow access.
   *
   * @param array $checks
   *   Contains the list of checks on the route definition.
   * @param \Drupal\Component\Utility\ArgumentsResolverInterface $arguments_resolver
   *   The parametrized arguments resolver instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Access\AccessResultInterface::andIf()
   */
  protected function checkAll(array $checks, ArgumentsResolverInterface $arguments_resolver) {
    $results = array();

    foreach ($checks as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      $result = $this->performCheck($service_id, $arguments_resolver);
      $results[] = $result;

      // Stop as soon as the first non-allowed check is encountered.
      if (!$result->isAllowed()) {
        break;
      }
    }

    if (empty($results)) {
      // No opinion.
      return AccessResult::create();
    }
    else {
      /** @var \Drupal\Core\Access\AccessResultInterface $result */
      $result = array_shift($results);
      foreach ($results as $other) {
        $result->andIf($other);
      }
      return $result;
    }
  }

  /**
   * Checks access so that at least one checker should allow access.
   *
   * @param array $checks
   *   Contains the list of checks on the route definition.
   * @param \Drupal\Component\Utility\ArgumentsResolverInterface $arguments_resolver
   *   The parametrized arguments resolver instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Access\AccessResultInterface::orIf()
   */
  protected function checkAny(array $checks, ArgumentsResolverInterface $arguments_resolver) {
    // No opinion by default.
    $result = AccessResult::create();

    foreach ($checks as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }
      $result = $result->orIf($this->performCheck($service_id, $arguments_resolver));
    }

    return $result;
  }

  /**
   * Performs the specified access check.
   *
   * @param string $service_id
   *   The access check service ID to use.
   * @param \Drupal\Component\Utility\ArgumentsResolverInterface $arguments_resolver
   *   The parametrized arguments resolver instance.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the access check returns an invalid value.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function performCheck($service_id, ArgumentsResolverInterface $arguments_resolver) {
    $callable = array($this->checks[$service_id], $this->checkMethods[$service_id]);
    $arguments = $arguments_resolver->getArguments($callable);
    /** @var \Drupal\Core\Access\AccessResultInterface $service_access **/
    $service_access = call_user_func_array($callable, $arguments);

    if (!$service_access instanceof AccessResultInterface) {
      throw new AccessException("Access error in $service_id. Access services must return an object that implements AccessResultInterface.");
    }

    return $service_access;
  }

  /**
   * Lazy-loads access check services.
   *
   * @param string $service_id
   *   The service id of the access check service to load.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the service hasn't been registered in addCheckService().
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the service doesn't implement the required interface.
   */
  protected function loadCheck($service_id) {
    if (!in_array($service_id, $this->checkIds)) {
      throw new \InvalidArgumentException(sprintf('No check has been registered for %s', $service_id));
    }

    $check = $this->container->get($service_id);

    if (!($check instanceof AccessInterface)) {
      throw new AccessException('All access checks must implement AccessInterface.');
    }
    if (!is_callable(array($check, $this->checkMethods[$service_id]))) {
      throw new AccessException(sprintf('Access check method %s in service %s must be callable.', $this->checkMethods[$service_id], $service_id));
    }

    $this->checks[$service_id] = $check;
  }

  /**
   * Compiles a mapping of requirement keys to access checker service IDs.
   */
  protected function loadDynamicRequirementMap() {
    if (isset($this->dynamicRequirementMap)) {
      return;
    }

    // Set them here, so we can use the isset() check above.
    $this->dynamicRequirementMap = array();

    foreach ($this->checkIds as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      // Add the service ID to an array that will be iterated over.
      if ($this->checks[$service_id] instanceof AccessCheckInterface) {
        $this->dynamicRequirementMap[] = $service_id;
      }
    }
  }

}
