<?php

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
   *   An array of entities implementing \Drupal\Core\Entity\EntityInterface
   *   indexed by their IDs. Returns an empty array if no matching entities are
   *   found.
   */
  public function load();

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * phpcs:disable Drupal.Commenting
   * @todo Uncomment new method parameters before drupal:12.0.0.
   * @see https://www.drupal.org/project/drupal/issues/3533078
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   The cacheable metadata to add to if your operations vary by or depend on
   *   something.
   * phpcs:enable
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations(EntityInterface $entity /* , ?CacheableDependencyInterface $cacheability = NULL */);

  /**
   * Builds a listing of entities for the given entity type.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function render();

}
