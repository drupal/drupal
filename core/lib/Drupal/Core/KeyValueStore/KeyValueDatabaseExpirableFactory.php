<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseExpirableFactory implements KeyValueExpirableFactoryInterface, DestructableInterface {

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var array
   */
  protected $storages;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
