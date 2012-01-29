<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\mysql\Truncate
 */

namespace Drupal\Core\Database\Driver\mysql;

use Drupal\Core\Database\Query\Truncate as QueryTruncate;

class Truncate extends QueryTruncate {
  public function __toString() {
    // TRUNCATE is actually a DDL statement on MySQL, and DDL statements are
    // not transactional, and result in an implicit COMMIT. When we are in a
    // transaction, fallback to the slower, but transactional, DELETE.
    if ($this->connection->inTransaction()) {
      // Create a comment string to prepend to the query.
      $comments = $this->connection->makeComment($this->comments);
      return $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '}';
    }
    else {
      return parent::__toString();
    }
  }
}
