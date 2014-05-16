<?php

/**
 * @file
 * Contains \Drupal\Core\Access\CustomAccessCheck.
 */

namespace Drupal\Core\Access;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
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
   * @var \Drupal\Core\Access\AccessArgumentsResolverInterface
   */
  protected $argumentsResolver;

  /**
   * Constructs a CustomAccessCheck instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Access\AccessArgumentsResolverInterface $arguments_resolver
   *   The arguments resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, AccessArgumentsResolverInterface $arguments_resolver) {
    $this->controllerResolver = $controller_resolver;
    $this->argumentsResolver = $arguments_resolver;
  }

  /**
   * Checks access for the account and route using the custom access checker.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $callable = $this->controllerResolver->getControllerFromDefinition($route->getRequirement('_custom_access'));
    $arguments = $this->argumentsResolver->getArguments($callable, $route, $request, $account);
    return call_user_func_array($callable, $arguments);
  }

}
