<?php

/**
 * @file
 * Definition of Drupal\field_sql_storage\Entity\Tables.
 */

namespace Drupal\field_sql_storage\Entity;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\QueryException;

/**
 * Adds tables and fields to the SQL entity query.
 */
class Tables {

  /**
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $sqlQuery;

  /**
   * Entity table array, key is table name, value is alias.
   *
   * This array contains at most two entries: one for the data, one for the
   * properties.
   *
   * @var array
   */
  protected $entityTables = array();


  /**
   * Field table array, key is table name, value is alias.
   *
   * This array contains one entry per field table.
   *
   * @var array
   */
  protected $fieldTables = array();

  /**
   * @param \Drupal\Core\Database\Query\SelectInterface $sql_query
   */
  function __construct(SelectInterface $sql_query) {
    $this->sqlQuery = $sql_query;
  }

  /**
   * @param string $field
   *   If it contains a dot, then field name dot field column. If it doesn't
   *   then entity property name.
   * @param string $type
   *   Join type, can either be INNER or LEFT.
   * @return string
   *   The return value is a string containing the alias of the table, a dot
   *   and the appropriate SQL column as passed in. This allows the direct use
   *   of this in a query for a condition or sort.
   */
  function addField($field, $type, $langcode) {
    $parts = explode('.', $field);
    $property = $parts[0];
    $configurable_fields = $this->sqlQuery->getMetaData('configurable_fields');
    if (!empty($configurable_fields[$property]) || substr($property, 0, 3) == 'id:') {
      $field_name = $property;
      $table = $this->ensureFieldTable($field_name, $type, $langcode);
      // Default to .value.
      $column = isset($parts[1]) ? $parts[1] : 'value';
      $sql_column = _field_sql_storage_columnname($field_name, $column);
    }
    else {
      $sql_column = $property;
      $table = $this->ensureEntityTable($property, $type, $langcode);
    }
    return "$table.$sql_column";
  }

  /**
   * Join entity table if necessary and return the alias for it.
   *
   * @param string $property
   * @return string
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureEntityTable($property, $type, $langcode) {
    $entity_tables = $this->sqlQuery->getMetaData('entity_tables');
    if (!$entity_tables) {
      throw new QueryException('Can not query entity properties without entity tables.');
    }
    foreach ($entity_tables as $table => $schema) {
      if (isset($schema['fields'][$property])) {
        if (!isset($this->entityTables[$table])) {
          $id_field = $this->sqlQuery->getMetaData('entity_id_field');
          $this->entityTables[$table] = $this->addJoin($type, $table, "%alias.$id_field = base_table.$id_field", $langcode);
        }
        return $this->entityTables[$table];
      }
    }
    throw new QueryException(format_string('@property not found', array('@property' => $property)));
  }

  /**
   * Join field table if necessary.
   *
   * @param $field_name
   *   Name of the field.
   * @return string
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  protected function ensureFieldTable(&$field_name, $type, $langcode) {
    if (!isset($this->fieldTables[$field_name])) {
      // This is field_purge_batch() passing in a field id.
      if (substr($field_name, 0, 3) == 'id:') {
        $field = field_info_field_by_id(substr($field_name, 3));
      }
      else {
        $field = field_info_field($field_name);
      }
      if (!$field) {
        throw new QueryException(format_string('field @field_name not found', array('@field_name' => $field_name)));
      }
      // This is really necessary only for the id: case but it can't be run
      // before throwing the exception.
      $field_name = $field['field_name'];
      $table = $this->sqlQuery->getMetaData('age') == FIELD_LOAD_CURRENT ? _field_sql_storage_tablename($field) : _field_sql_storage_revision_tablename($field);
      $field_id_field = $this->sqlQuery->getMetaData('field_id_field');
      $entity_id_field = $this->sqlQuery->getMetaData('entity_id_field');
      if ($field['cardinality'] != 1) {
        $this->sqlQuery->addMetaData('simple_query', FALSE);
      }
      $this->fieldTables[$field_name] = $this->addJoin($type, $table, "%alias.$field_id_field = base_table.$entity_id_field", $langcode);
    }
    return $this->fieldTables[$field_name];
  }

  protected function addJoin($type, $table, $join_condition, $langcode) {
    $arguments = array();
    if ($langcode) {
      $placeholder = ':langcode' . $this->sqlQuery->nextPlaceholder();
      $join_condition .= ' AND %alias.langcode = ' . $placeholder;
      $arguments[$placeholder] = $langcode;
    }
    return $this->sqlQuery->addJoin($type, $table, NULL, $join_condition, $arguments);
  }

}
