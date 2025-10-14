<?php

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a trait for automatically wiring dependencies from the container.
 *
 * This trait uses reflection and may cause performance issues with classes
 * that will be instantiated multiple times.
 */
trait AutowireTrait {

  use AutowiredInstanceTrait;

  /**
   * Instantiates a new instance of the implementing class using autowiring.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return static::createInstanceAutowired($container);
  }

}
