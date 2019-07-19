<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for helper methods for loading entity types.
 */
interface EntityTypeRepositoryInterface {

  /**
   * Builds a list of entity type labels suitable for a Form API options list.
   *
   * @param bool $group
   *   (optional) Whether to group entity types by plugin group (e.g. 'content',
   *   'config'). Defaults to FALSE.
   *
   * @return array
   *   An array of entity type labels, keyed by entity type name.
   */
  public function getEntityTypeLabels($group = FALSE);

  /**
   * Gets the entity type ID based on the class that is called on.
   *
   * Compares the class this is called on against the known entity classes
   * and returns the entity type ID of a direct match or a subclass as fallback,
   * to support entity type definitions that were altered.
   *
   * @param string $class_name
   *   Class name to use for searching the entity type ID.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\Entity\Exception\AmbiguousEntityClassException
   *   Thrown when multiple subclasses correspond to the called class.
   * @throws \Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException
   *   Thrown when no entity class corresponds to the called class.
   *
   * @see \Drupal\Core\Entity\Entity::load()
   * @see \Drupal\Core\Entity\Entity::loadMultiple()
   */
  public function getEntityTypeFromClass($class_name);

  /**
   * Clear the static cache.
   *
   * @deprecated in drupal:8.0.0 and is removed in drupal:9.0.0.
   */
  public function clearCachedDefinitions();

}
