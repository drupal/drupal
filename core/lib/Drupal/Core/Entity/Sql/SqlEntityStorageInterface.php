<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\SqlEntityStorageInterface.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A common interface for SQL-based entity storage implementations.
 */
interface SqlEntityStorageInterface extends EntityStorageInterface {

  /**
   * Gets a table mapping for the entity's SQL tables.
   *
   * @return \Drupal\Core\Entity\Sql\TableMappingInterface
   *   A table mapping object for the entity's tables.
   */
  public function getTableMapping();

}
