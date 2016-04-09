<?php

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * A common interface for SQL-based entity storage implementations.
 */
interface SqlEntityStorageInterface extends EntityStorageInterface {

  /**
   * Gets a table mapping for the entity's SQL tables.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   (optional) An array of field storage definitions to be used to compute
   *   the table mapping. Defaults to the ones provided by the entity manager.
   *
   * @return \Drupal\Core\Entity\Sql\TableMappingInterface
   *   A table mapping object for the entity's tables.
   */
  public function getTableMapping(array $storage_definitions = NULL);

}
