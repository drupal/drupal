<?php

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * MySQL implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);
    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $insert_fields);

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    // Updating the unique / primary key is not necessary.
    unset($insert_fields[$this->key]);

    $update = [];
    foreach ($insert_fields as $field) {
      $update[] = "$field = VALUES($field)";
    }

    $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update);

    return $query;
  }

}
