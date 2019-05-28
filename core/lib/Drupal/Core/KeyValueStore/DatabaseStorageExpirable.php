<?php

namespace Drupal\Core\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class DatabaseStorageExpirable extends DatabaseStorage implements KeyValueStoreExpirableInterface {

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value_expire.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value_expire') {
    parent::__construct($collection, $serializer, $connection, $table);
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return (bool) $this->connection->query('SELECT 1 FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection AND name = :key AND expire > :now', [
      ':collection' => $this->collection,
      ':key' => $key,
      ':now' => REQUEST_TIME,
    ])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $values = $this->connection->query(
      'SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE expire > :now AND name IN ( :keys[] ) AND collection = :collection',
      [
        ':now' => REQUEST_TIME,
        ':keys[]' => $keys,
        ':collection' => $this->collection,
      ])->fetchAllKeyed();
    return array_map([$this->serializer, 'decode'], $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $values = $this->connection->query(
      'SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection AND expire > :now',
      [
        ':collection' => $this->collection,
        ':now' => REQUEST_TIME,
      ])->fetchAllKeyed();
    return array_map([$this->serializer, 'decode'], $values);
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpire($key, $value, $expire) {
    $this->connection->merge($this->table)
      ->keys([
        'name' => $key,
        'collection' => $this->collection,
      ])
      ->fields([
        'value' => $this->serializer->encode($value),
        'expire' => REQUEST_TIME + $expire,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function setWithExpireIfNotExists($key, $value, $expire) {
    if (!$this->has($key)) {
      $this->setWithExpire($key, $value, $expire);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $key => $value) {
      $this->setWithExpire($key, $value, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    parent::deleteMultiple($keys);
  }

}
