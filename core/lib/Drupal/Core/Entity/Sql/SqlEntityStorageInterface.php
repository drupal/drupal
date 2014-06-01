<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Schema\EntitySchemaProviderInterface;

/**
 * A common interface for SQL-based storage controllers.
 */
interface SqlEntityStorageInterface extends EntityStorageInterface, EntitySchemaProviderInterface {

  /**
   * Gets a table mapping for the entity's SQL tables.
   *
   * @return \Drupal\Core\Entity\Sql\TableMappingInterface
   *   A table mapping object for the entity's tables.
   */
  public function getTableMapping();

}
