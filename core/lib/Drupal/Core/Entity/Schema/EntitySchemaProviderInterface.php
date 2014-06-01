<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Schema\EntitySchemaProviderInterface.
 */

namespace Drupal\Core\Entity\Schema;

/**
 * Defines a common interface to return the storage schema for entities.
 */
interface EntitySchemaProviderInterface {

  /**
   * Gets the full schema array for a given entity type.
   *
   * @return array
   *   A schema array for the entity type's tables.
   */
  public function getSchema();

}
