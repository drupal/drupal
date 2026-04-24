<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Upsert.
 *
 * @see https://www.sqlite.org/lang_UPSERT.html
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    $keys = array_map(function ($key) {
      return $this->connection->escapeField($key);
    }, $this->key);

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);
    $insert_fields = array_combine($insert_fields, $insert_fields);
    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $insert_fields);

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    // Updating the unique / primary key fields is not necessary.
    foreach ($this->key as $key) {
      unset($insert_fields[$key]);
    }

    $update = [];
    foreach ($insert_fields as $field) {
      // The "excluded." prefix causes the field to refer to the value for field
      // that would have been inserted had there been no conflict.
      $update[] = "$field = EXCLUDED.$field";
    }

    $query .= ' ON CONFLICT (' . implode(', ', $keys) . ') DO UPDATE SET ' . implode(', ', $update);

    return $query;
  }

}
