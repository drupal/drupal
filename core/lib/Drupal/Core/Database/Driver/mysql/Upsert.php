<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\mysql\Upsert.
 */

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * Implements the Upsert query for the MySQL database driver.
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
