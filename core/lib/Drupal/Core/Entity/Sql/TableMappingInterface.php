<?php

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
   * A property that represents delta used in entity query conditions.
   */
  const DELTA = '%delta';

  /**
   * Gets a list of table names for this mapping.
   *
   * @return string[]
   *   An array of table names.
   */
  public function getTableNames();

  /**
   * Gets a list of all database columns for a given table.
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
   * Gets a list of names for entity fields stored in the specified table.
   *
   * The return list is contains the entity field names, not database field
   * (i.e. column) names. To get the mapping of specific entity field to
   * database columns use ::getColumnNames().
   *
   * @param string $table_name
   *   The name of the table to return the field names for.
   *
   * @return string[]
   *   An array of field names for the given table.
   */
  public function getFieldNames($table_name);

  /**
   * Gets a mapping of field columns to database columns for a given field.
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
   * Gets a list of extra database columns, which store denormalized data.
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
   * Gets the list of columns that can not be used as field type columns.
   *
   * @return array
   *   A list of column names prohibited from being used as a field type column.
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
   *   A string containing a generated column name for a field data table that
   *   is unique among all other fields.
   */
  public function getFieldColumnName(FieldStorageDefinitionInterface $storage_definition, $property_name);

  /**
   * Gets the table name for a given column.
   *
   * @param string $field_name
   *   The name of the entity field to return the column mapping for.
   *
   * @return string
   *   Table name for the given field.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  public function getFieldTableName($field_name);

  /**
   * Gets all the table names in which an entity field is stored.
   *
   * The returned table names are ordered by the amount of data stored in each
   * table. For example, a revisionable and translatable entity type which uses
   * core's default table mapping strategy would return the table names for the
   * entity ID field in the following order:
   * - base table
   * - data table
   * - revision table
   * - revision data table
   *
   * @param string $field_name
   *   The name of the entity field to return the tables names for.
   *
   * @return string[]
   *   An array of table names in which the given field is stored.
   *
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   */
  public function getAllFieldTableNames($field_name);

}
