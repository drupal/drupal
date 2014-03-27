<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityListBuilderInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines an interface to build entity listings.
 */
interface EntityListBuilderInterface {

  /**
   * Gets the entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage used by this list builder.
   */
  public function getStorage();

  /**
   * Loads entities of this type from storage for listing.
   *
   * This allows the implementation to manipulate the listing, like filtering or
   * sorting the loaded entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities implementing \Drupal\Core\Entity\EntityInterface.
   */
  public function load();

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - href: The path for the operation.
   *   - options: An array of URL options for the path.
   *   - weight: The weight of this operation.
   */
  public function getOperations(EntityInterface $entity);

  /**
   * Returns a listing of entities for the given entity type.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function render();

}
