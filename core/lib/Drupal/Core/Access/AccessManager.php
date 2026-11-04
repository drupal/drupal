<?php

namespace Drupal\Core\Access;

use Drupal\Core\ParamConverter\ParamConverterManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\ArgumentsResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Routing\RouteObjectInterface;

/**
 * Attaches access check services to routes and runs them on request.
 *
 * @see \Drupal\Tests\Core\Access\AccessManagerTest
 */
class AccessManager implements AccessManagerInterface {

  /**
   * Constructs an AccessManager instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider.
   * @param \Drupal\Core\ParamConverter\ParamConverterManagerInterface $paramConverterManager
   *   The param converter manager.
   * @param \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface $argumentsResolverFactory
   *   The access arguments resolver.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param CheckProviderInterface $checkProvider
   *   The check access provider.
   */
  public function __construct(
    protected RouteProviderInterface $routeProvider,
    protected ParamConverterManagerInterface $paramConverterManager,
    protected AccessArgumentsResolverFactoryInterface $argumentsResolverFactory,
    protected AccountInterface $currentUser,
    protected CheckProviderInterface $checkProvider,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function checkNamedRoute($route_name, array $parameters = [], ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    try {
      $route = $this->routeProvider->getRouteByName($route_name);

      // ParamConverterManager relies on the route name and object being
      // available from the parameters array.
      $parameters[RouteObjectInterface::ROUTE_NAME] = $route_name;
      $parameters[RouteObjectInterface::ROUTE_OBJECT] = $route;
      $upcasted_parameters = $this->paramConverterManager->convert($parameters + $route->getDefaults());

      $route_match = new RouteMatch($route_name, $route, $upcasted_parameters, $parameters);
      return $this->check($route_match, $account, NULL, $return_as_object);
    }
    catch (RouteNotFoundException) {
      // Cacheable until extensions change.
      $result = AccessResult::forbidden()->addCacheTags(['config:core.extension']);
      return $return_as_object ? $result : $result->isAllowed();
    }
    catch (ParamNotConvertedException) {
      // Uncacheable because conversion of the parameter may not have been
      // possible due to dynamic circumstances.
      $result = AccessResult::forbidden()->setCacheMaxAge(0);
      return $return_as_object ? $result : $result->isAllowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequest(Request $request, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $route_match = RouteMatch::createFromRequest($request);
    return $this->check($route_match, $account, $request, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function check(RouteMatchInterface $route_match, ?AccountInterface $account = NULL, ?Request $request = NULL, $return_as_object = FALSE) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }
    $route = $route_match->getRouteObject();
    $checks = $route->getOption('_access_checks') ?: [];

    // Filter out checks which require the incoming request.
    if (!isset($request)) {
      $checks = array_diff($checks, $this->checkProvider->getChecksNeedRequest());
    }

    $result = AccessResult::neutral();
    if (!empty($checks)) {
      $arguments_resolver = $this->argumentsResolverFactory->getArgumentsResolver($route_match, $account, $request);
      $result = AccessResult::allowed();
      foreach ($checks as $service_id) {
        $result = $result->andIf($this->performCheck($service_id, $arguments_resolver));
        // Stop as soon as the first non-allowed check is encountered. Neutral
        // or forbidden results will always deny access in the end, so there is
        // no need to run any extra code or add any extra cacheability after we
        // know the route will be inaccessible.
        if (!$result->isAllowed()) {
          break;
        }
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Performs the specified access check.
   *
   * @param string $service_id
   *   The access check service ID to use.
   * @param \Drupal\Component\Utility\ArgumentsResolverInterface $arguments_resolver
   *   The parametrized arguments resolver instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the access check returns an invalid value.
   */
  protected function performCheck($service_id, ArgumentsResolverInterface $arguments_resolver) {
    $callable = $this->checkProvider->loadCheck($service_id);
    $arguments = $arguments_resolver->getArguments($callable);
    /** @var \Drupal\Core\Access\AccessResultInterface $service_access **/
    $service_access = call_user_func_array($callable, $arguments);

    if (!$service_access instanceof AccessResultInterface) {
      throw new AccessException("Access error in $service_id. Access services must return an object that implements AccessResultInterface.");
    }

    return $service_access;
  }

}
