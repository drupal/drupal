<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\DatabaseRowSelect.
 */

namespace Drupal\Core\Database\Driver\fake;

class DatabaseRowSelect extends DatabaseRow {

  /**
   * Construct a new database row.
   *
   * @param array $row
   *   The row data.
   * @param array $fields_with_table
   *   The fields with a table.
   * @param array $fields
   *   The fields.
   */
  public function __construct(array $row, array $fields_with_table, array $fields) {
    $this->fieldsWithTable = $fields_with_table;
    $this->fields = $fields;
    parent::__construct($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($field) {
    $field_info = isset($this->fieldsWithTable[$field]) ? $this->fieldsWithTable[$field] : $this->fields[$field];
    if (array_key_exists($field_info['field'], $this->row[$field_info['table']]['result'])) {
      $index = 'result';
    }
    else {
      $index = 'all';
    }
    return $this->row[$field_info['table']][$index][$field_info['field']];
  }

}
