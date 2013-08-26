<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ControllerResolverInterface.
 */

namespace Drupal\Core\Controller;

use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface as BaseControllerResolverInterface;

/**
 * Extends the ControllerResolverInterface from symfony.
 */
interface ControllerResolverInterface extends BaseControllerResolverInterface {

  /**
   * Returns the Controller instance with a given controller route definition.
   *
   * As several resolvers can exist for a single application, a resolver must
   * return false when it is not able to determine the controller.
   *
   * @param mixed $controller
   *   The controller attribute like in $request->attributes->get('_controller')
   *
   * @return mixed|bool
   *   A PHP callable representing the Controller, or false if this resolver is
   *   not able to determine the controller
   *
   * @throws \InvalidArgumentException|\LogicException
   *   Thrown if the controller can't be found.
   *
   * @see \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface::getController()
   */
  public function getControllerFromDefinition($controller);

}
