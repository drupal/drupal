<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityHandlerInterface.
 */

namespace Drupal\Core\Entity;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an interface for entity handlers.
 *
 * This interface can be implemented by entity handlers that require
 * dependency injection.
 */
interface EntityHandlerInterface {

  /**
   * Instantiates a new instance of this entity handler.
   *
   * This is a factory method that returns a new instance of this object. The
   * factory should pass any needed dependencies into the constructor of this
   * object, but not the container itself. Every call to this method must return
   * a new instance of this object; that is, it may not implement a singleton.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return static
   *   A new instance of the entity handler.
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type);

}
