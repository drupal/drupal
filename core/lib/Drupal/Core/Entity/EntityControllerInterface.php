<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityControllerInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a common interface for entity controllers.
 *
 * This interface can be implemented by entity controllers that require
 * dependency injection. These are not controllers in the routing sense of the
 * word, but instead are handlers that perform a specific function for an entity
 * type.
 */
interface EntityControllerInterface {

  /**
   * Instantiates a new instance of this entity controller.
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
   *   A new instance of the entity controller.
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type);

}
