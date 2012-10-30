<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseExpirableFactory extends KeyValueDatabaseFactory {

  /**
   * Constructs a new key/value expirable database storage object for a given
   * collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to run against.
   * @return \Drupal\Core\KeyValueStore\DatabaseStorageExpirable
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    return new DatabaseStorageExpirable($collection, $this->connection);
  }
}
