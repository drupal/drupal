<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\sqlite\Truncate.
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Query\Truncate as QueryTruncate;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Truncate.
 *
 * SQLite doesn't support TRUNCATE, but a DELETE query with no condition has
 * exactly the effect (it is implemented by DROPing the table).
 */
class Truncate extends QueryTruncate {
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    return $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '} ';
  }
}
