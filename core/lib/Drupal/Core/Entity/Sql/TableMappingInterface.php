<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Sql\TableMappingInterface.
 */

namespace Drupal\Core\Entity\Sql;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides a common interface for mapping field columns to SQL tables.
 *
 * Warning: using methods provided here should be done only when writing code
 * that is explicitly targeting a SQL-based entity storage. Typically this API
 * is used by SQL storage classes, or other SQL-specific code like the Views
 * integration code for the Entity SQL storage. Another example of legal usage
 * of this API is when needing to write a query that \Drupal::entityQuery() does
 * not support. Always retrieve entity identifiers and use them to load entities
 * instead of accessing data stored in the database directly. Any other usage
 * circumvents the entity system and is strongly discouraged, at least when
 * writing contributed code.
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

  /**
   * A list of columns that can not be used as field type columns.
   *
   * @return array
   */
  public function getReservedColumns();

  /**
   * Generates a column name for a field property.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param string $property_name
   *   The name of the property.
   *
   * @return string
   *   A string containing a generated column name for a field data table that is
   *   unique among all other fields.
   */
  public function getFieldColumnName(FieldStorageDefinitionInterface $storage_definition, $property_name);
}
