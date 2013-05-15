<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ControllerInterface.
 */

namespace Drupal\Core\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a common interface for route controllers.
 */
interface ControllerInterface {

  /**
   * Instantiates a new instance of this controller.
   *
   * This is a factory method that returns a new instance of this object. The
   * factory should pass any needed dependencies into the constructor of this
   * object, but not the container itself. Every call to this method must return
   * a new instance of this object; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   */
  public static function create(ContainerInterface $container);
}
