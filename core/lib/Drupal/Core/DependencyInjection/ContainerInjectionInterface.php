<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ContainerInjectionInterface.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a common interface for route controllers.
 *
 * This interface gives controller classes a factory method for instantiation
 * rather than relying on a services.yml entry. However, it may result in
 * a lot of boilerplate code in the class. As an alternative, controllers that
 * contain only limited glue code ("thin" controllers) should instead extend
 * ControllerBase as that allows direct access to the container. That renders
 * the controller very difficult to unit test so should only be used for
 * controllers that are trivial in complexity.
 */
interface ContainerInjectionInterface {

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
