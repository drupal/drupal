<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\sqlite\Delete
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Query\Delete as QueryDelete;

/**
 * SQLite specific implementation of DeleteQuery.
 *
 * When the WHERE is omitted from a DELETE statement and the table being deleted
 * has no triggers, SQLite uses an optimization to erase the entire table content
 * without having to visit each row of the table individually.
 *
 * Prior to SQLite 3.6.5, SQLite does not return the actual number of rows deleted
 * by that optimized "truncate" optimization.
 */
class Delete extends QueryDelete {
  public function execute() {
    if (!count($this->condition)) {
      $total_rows = $this->connection->query('SELECT COUNT(*) FROM {' . $this->connection->escapeTable($this->table) . '}')->fetchField();
      parent::execute();
      return $total_rows;
    }
    else {
      return parent::execute();
    }
  }
}
