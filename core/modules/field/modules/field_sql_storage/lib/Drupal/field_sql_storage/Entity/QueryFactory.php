<?php

/**
 * @file
 * Definition of Drupal\field_sql_storage\Entity\QueryFactory.
 */

namespace Drupal\field_sql_storage\Entity;

use Drupal\Core\Database\Connection;

/**
 * Factory class creating entity query objects for the SQL backend.
 */
class QueryFactory {

  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  function get($entity_type, $conjunction) {
    return new Query($entity_type, $conjunction, $this->connection);
  }
}
