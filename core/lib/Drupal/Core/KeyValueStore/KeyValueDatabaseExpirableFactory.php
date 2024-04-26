<?php

namespace Drupal\Core\KeyValueStore;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseExpirableFactory implements KeyValueExpirableFactoryInterface {

  /**
   * Holds references to each instantiation so they can be terminated.
   *
   * @var \Drupal\Core\KeyValueStore\DatabaseStorageExpirable[]
   */
  protected $storages = [];

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected SerializationInterface $serializer,
    protected Connection $connection,
    protected TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->storages[$collection])) {
      $this->storages[$collection] = new DatabaseStorageExpirable($collection, $this->serializer, $this->connection, $this->time);
    }
    return $this->storages[$collection];
  }

  /**
   * Deletes expired items.
   */
  public function garbageCollection() {
    try {
      $this->connection->delete('key_value_expire')
        ->condition('expire', $this->time->getRequestTime(), '<')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Act on an exception when the table might not have been created.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the exception needs to propagate.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e) {
    if ($this->connection->schema()->tableExists('key_value_expire')) {
      throw $e;
    }
  }

}
