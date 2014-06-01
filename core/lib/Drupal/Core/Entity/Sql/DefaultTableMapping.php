<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\DefaultTableMapping.
 */

namespace Drupal\Core\Entity\Sql;

/**
 * Defines a default table mapping class.
 */
class DefaultTableMapping implements TableMappingInterface {

  /**
   * A list of field storage definitions that are available for this mapping.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   */
  protected $fieldStorageDefinitions = array();

  /**
   * A list of field names per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getFieldNames() except that this variable is
   * additionally keyed by table name.
   *
   * @var array[]
   */
  protected $fieldNames = array();

  /**
   * A list of database columns which store denormalized data per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getExtraColumns() except that this variable is
   * additionally keyed by table name.
   *
   * @var array[]
   */
  protected $extraColumns = array();

  /**
   * A mapping of column names per field name.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getColumnNames() except that this variable is
   * additionally keyed by field name.
   *
   * This data is derived from static::$storageDefinitions, but is stored
   * separately to avoid repeated processing.
   *
   * @var array[]
   */
  protected $columnMapping = array();

  /**
   * A list of all database columns per table.
   *
   * This corresponds to the return value of
   * TableMappingInterface::getAllColumns() except that this variable is
   * additionally keyed by table name.
   *
   * This data is derived from static::$storageDefinitions, static::$fieldNames,
   * and static::$extraColumns, but is stored separately to avoid repeated
   * processing.
   *
   * @var array[]
   */
  protected $allColumns = array();

  /**
   * Constructs a DefaultTableMapping.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions
   *   A list of field storage definitions that should be available for the
   *   field columns of this table mapping.
   */
  public function __construct(array $storage_definitions) {
    $this->fieldStorageDefinitions = $storage_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableNames() {
    return array_unique(array_merge(array_keys($this->fieldNames), array_keys($this->extraColumns)));
  }

  /**
   * {@inheritdoc}
   */
  public function getAllColumns($table_name) {
    if (!isset($this->allColumns[$table_name])) {
      $this->allColumns[$table_name] = array();

      foreach ($this->getFieldNames($table_name) as $field_name) {
        $this->allColumns[$table_name] = array_merge($this->allColumns[$table_name], array_values($this->getColumnNames($field_name)));
      }

      $this->allColumns[$table_name] = array_merge($this->allColumns[$table_name], $this->getExtraColumns($table_name));
    }
    return $this->allColumns[$table_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames($table_name) {
    if (isset($this->fieldNames[$table_name])) {
      return $this->fieldNames[$table_name];
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnNames($field_name) {
    if (!isset($this->columnMapping[$field_name])) {
      $column_names = array_keys($this->fieldStorageDefinitions[$field_name]->getColumns());
      if (count($column_names) == 1) {
        $this->columnMapping[$field_name] = array(reset($column_names) => $field_name);
      }
      else {
        $this->columnMapping[$field_name] = array();
        foreach ($column_names as $column_name) {
          $this->columnMapping[$field_name][$column_name] = $field_name . '__' . $column_name;
        }
      }
    }
    return $this->columnMapping[$field_name];
  }

  /**
   * Adds field columns for a table to the table mapping.
   *
   * @param string $table_name
   *   The name of the table to add the field column for.
   * @param string[] $field_names
   *   A list of field names to add the columns for.
   *
   * @return $this
   */
  public function setFieldNames($table_name, array $field_names) {
    $this->fieldNames[$table_name] = $field_names;
    // Force the re-computation of the column list.
    unset($this->allColumns[$table_name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraColumns($table_name) {
    if (isset($this->extraColumns[$table_name])) {
      return $this->extraColumns[$table_name];
    }
    return array();
  }

  /**
   * Adds a extra columns for a table to the table mapping.
   *
   * @param string $table_name
   *   The name of table to add the extra columns for.
   * @param string[] $column_names
   *   The list of column names.
   *
   * @return $this
   */
  public function setExtraColumns($table_name, array $column_names) {
    $this->extraColumns[$table_name] = $column_names;
    // Force the re-computation of the column list.
    unset($this->allColumns[$table_name]);
    return $this;
  }

}
