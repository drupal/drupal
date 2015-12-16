<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ClassResolverInterface.
 */

namespace Drupal\Core\DependencyInjection;

/**
 * Provides an interface to get a instance of a class with dependency injection.
 */
interface ClassResolverInterface {

  /**
   * Returns a class instance with a given class definition.
   *
   * In contrast to controllers you don't specify a method.
   *
   * @param string $definition
   *   A class name or service name.
   *
   * @return object
   *   The instance of the class.
   *
   * @throws \InvalidArgumentException
   *   If $class is not a valid service identifier and the class does not exist.
   */
  public function getInstanceFromDefinition($definition);

}
