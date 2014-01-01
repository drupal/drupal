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
   * Constructs a CustomAccessCheck instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver) {
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $access_controller = $route->getRequirement('_custom_access');

    $controller = $this->controllerResolver->getControllerFromDefinition($access_controller);
    $arguments = $this->controllerResolver->getArguments($request, $controller);

    return call_user_func_array($controller, $arguments);
  }

}
