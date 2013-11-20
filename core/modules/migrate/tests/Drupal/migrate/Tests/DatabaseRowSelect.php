<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\DatabaseRowSelect.
 */

namespace Drupal\migrate\Tests;

class DatabaseRowSelect extends DatabaseRow {

  public function __construct(array $row, array $fieldsWithTable, array $fields) {
    $this->fieldsWithTable = $fieldsWithTable;
    $this->fields = $fields;
    parent::__construct($row);
  }

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
