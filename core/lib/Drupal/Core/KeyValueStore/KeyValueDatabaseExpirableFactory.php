<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseExpirableFactory implements KeyValueExpirableFactoryInterface, DestructableInterface {

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var \Drupal\Core\KeyValueStore\DatabaseStorageExpirable[]
   */
  protected $storages = array();

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct(SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      $this->storages[$collection] = new DatabaseStorageExpirable($collection, $this->serializer, $this->connection);
    }
    return $this->storages[$collection];
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if (!empty($this->storages)) {
      // Each instance does garbage collection for all collections, so we can
      // optimize and only have to call the first, avoids multiple DELETE.
      $storage = reset($this->storages);
      $storage->destruct();
    }
  }
}
