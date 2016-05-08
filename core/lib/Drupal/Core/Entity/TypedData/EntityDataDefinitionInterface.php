<?php

namespace Drupal\Core\Entity\TypedData;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;

/**
 * Interface for typed data entity definitions.
 */
interface EntityDataDefinitionInterface extends ComplexDataDefinitionInterface {

  /**
   * Gets the entity type ID.
   *
   * @return string|null
   *   The entity type ID, or NULL if the entity type is unknown.
   */
  public function getEntityTypeId();

  /**
   * Sets the entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type to set.
   *
   * @return $this
   */
  public function setEntityTypeId($entity_type_id);

  /**
   * Gets the array of possible entity bundles.
   *
   * @return array|null
   *   The array of possible bundles, or NULL for any.
   */
  public function getBundles();

  /**
   * Sets the array of possible entity bundles.
   *
   * @param array|null $bundles
   *   The array of possible bundles, or NULL for any.
   *
   * @return $this
   */
  public function setBundles(array $bundles = NULL);

}
