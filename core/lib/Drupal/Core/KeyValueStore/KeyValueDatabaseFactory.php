<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueDatabaseFactory.
 */

namespace Drupal\Core\KeyValueStore;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseFactory {

  /**
   * Constructs this factory object.
   *
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Constructs a new key/value database storage object for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to run against.
   * @return \Drupal\Core\KeyValueStore\DatabaseStorage
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    return new DatabaseStorage($collection, $this->connection);
  }
}
