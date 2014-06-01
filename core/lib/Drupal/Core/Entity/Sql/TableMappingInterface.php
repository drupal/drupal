<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\TableMappingInterface.
 */

namespace Drupal\Core\Entity\Sql;

/**
 * Provides a common interface for mapping field columns to SQL tables.
 */
interface TableMappingInterface {

  /**
   * Returns a list of table names for this mapping.
   *
   * @return string[]
   *   An array of table names.
   */
  public function getTableNames();

  /**
   * Returns a list of all database columns for a given table.
   *
   * @param string $table_name
   *   The name of the table to return the columns for.
   *
   * @return string[]
   *   An array of database column names for this table. Both field columns and
   *   extra columns are returned.
   */
  public function getAllColumns($table_name);

  /**
   * Returns a list of names of fields stored in the specified table.
   *
   * @param string $table_name
   *   The name of the table to return the field names for.
   *
   * @return string[]
   *   An array of field names for the given table.
   */
  public function getFieldNames($table_name);

  /**
   * Returns a mapping of field columns to database columns for a given field.
   *
   * @param string $field_name
   *   The name of the entity field to return the column mapping for.
   *
   * @return string[]
   *   The keys of this array are the keys of the array returned by
   *   FieldStorageDefinitionInterface::getColumns() while the respective values
   *   are the names of the database columns for this table mapping.
   */
  public function getColumnNames($field_name);

  /**
   * Returns a list of extra database columns, which store denormalized data.
   *
   * These database columns do not belong to any entity fields. Any normalized
   * data that is stored should be associated with an entity field.
   *
   * @param string $table_name
   *   The name of the table to return the columns for.
   *
   * @return string[]
   *   An array of column names for the given table.
   */
  public function getExtraColumns($table_name);

}
