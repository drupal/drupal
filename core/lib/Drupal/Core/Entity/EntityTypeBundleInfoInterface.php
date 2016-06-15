<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity type bundle info.
 */
interface EntityTypeBundleInfoInterface {

  /**
   * Get the bundle info of all entity types.
   *
   * @return array
   *   An array of bundle information where the outer array is keyed by entity
   *   type. The next level is keyed by the bundle name. The inner arrays are
   *   associative arrays of bundle information, such as the label for the
   *   bundle.
   */
  public function getAllBundleInfo();

  /**
   * Gets the bundle info of an entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   An array of bundle information where the outer array is keyed by the
   *   bundle name, or the entity type name if the entity does not have bundles.
   *   The inner arrays are associative arrays of bundle information, such as
   *   the label for the bundle.
   */
  public function getBundleInfo($entity_type);

  /**
   * Clears static and persistent bundles.
   */
  public function clearCachedBundles();

}
