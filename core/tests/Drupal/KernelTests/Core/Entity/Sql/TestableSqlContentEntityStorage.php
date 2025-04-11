<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity\Sql;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\TableMappingInterface;

/**
 * A test subclass of SqlContentEntityStorage.
 */
class TestableSqlContentEntityStorage extends SqlContentEntityStorage {

  /**
   * Make some properties public to allow manual injection of dependencies.
   */

  /**
   * {@inheritdoc}
   */
  public $database;

  /**
   * {@inheritdoc}
   */
  public $entityType;

  /**
   * {@inheritdoc}
   */
  public $fieldStorageDefinitions;

  /**
   * {@inheritdoc}
   */
  protected $tableMapping;

  /**
   * Override the constructor to bypass the parent's constructor.
   */
  public function __construct() {
    // Do nothing to bypass parent's constructor.
  }

  /**
   * Sets the table mapping.
   *
   * @param \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping
   *   The dummy table mapping.
   */
  public function setTableMapping(TableMappingInterface $table_mapping): void {
    $this->tableMapping = $table_mapping;
  }

  /**
   * Overrides original SqlContentEntityStorage::getTableMapping().
   *
   * {@inheritdoc}
   */
  public function getTableMapping(?array $storage_definitions = NULL) {
    return $this->tableMapping;
  }

  /**
   * Exposes the protected deleteFromDedicatedTables() method for testing.
   *
   * @param array $ids
   *   The array of entity IDs to delete.
   */
  public function publicDeleteFromDedicatedTables(array $ids): void {
    $this->deleteFromDedicatedTables($ids);
  }

}
