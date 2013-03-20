<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseExpirableFactory extends KeyValueDatabaseFactory implements DestructableInterface {

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var array
   */
  protected $storages;

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
    $storage = new DatabaseStorageExpirable($collection, $this->connection);
    $this->storages[] = $storage;
    return $storage;
  }

  /**
   * Implements Drupal\Core\DestructableInterface::terminate().
   */
  public function destruct() {
    foreach ($this->storages as $storage) {
      $storage->destruct();
    }
  }
}
