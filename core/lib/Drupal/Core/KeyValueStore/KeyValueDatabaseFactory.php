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
class KeyValueDatabaseFactory implements KeyValueFactoryInterface {

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
   * {@inheritdoc}
   */
  public function get($collection) {
    return new DatabaseStorage($collection, $this->connection);
  }
}
