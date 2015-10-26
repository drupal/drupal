<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\sqlite\Upsert.
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Upsert.
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

    $query = $comments . 'INSERT OR REPLACE INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    return $query;
  }

}
