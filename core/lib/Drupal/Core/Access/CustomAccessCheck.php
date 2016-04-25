<?php

namespace Drupal\Core\Access;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker that allows specifying a custom method for access.
 *
 * You should only use it when you are sure that the access callback will not be
 * reused. Good examples in core are Edit or Toolbar module.
 *
 * The method is called on another instance of the controller class, so you
 * cannot reuse any stored property of your actual controller instance used
 * to generate the output.
 */
class CustomAccessCheck implements RoutingAccessInterface {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface
   */
  protected $argumentsResolverFactory;

  /**
   * Constructs a CustomAccessCheck instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface $arguments_resolver_factory
   *   The arguments resolver factory.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, AccessArgumentsResolverFactoryInterface $arguments_resolver_factory) {
    $this->controllerResolver = $controller_resolver;
    $this->argumentsResolverFactory = $arguments_resolver_factory;
  }

  /**
   * Checks access for the account and route using the custom access checker.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $callable = $this->controllerResolver->getControllerFromDefinition($route->getRequirement('_custom_access'));
    $arguments_resolver = $this->argumentsResolverFactory->getArgumentsResolver($route_match, $account);
    $arguments = $arguments_resolver->getArguments($callable);

    return call_user_func_array($callable, $arguments);
  }

}
