<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\DatabaseStorageExpirable.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\DestructableInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Merge;

/**
 * Defines a default key/value store implementation for expiring items.
 *
 * This key/value store implementation uses the database to store key/value
 * data with an expire date.
 */
class DatabaseStorageExpirable extends DatabaseStorage implements KeyValueStoreExpirableInterface, DestructableInterface {

  /**
   * The connection object for this storage.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Flag indicating whether garbage collection should be performed.
   *
   * When this flag is TRUE, garbage collection happens at the end of the
   * request when the object is destructed. The flag is set during set and
   * delete operations for expirable data, when a write to the table is already
   * being performed. This eliminates the need for an external system to remove
   * stale data.
   *
   * @var bool
   */
  protected $needsGarbageCollection = FALSE;

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param array $options
   *   An associative array of options for the key/value storage collection.
   *   Keys used:
   *   - connection: (optional) The database connection to use for storing the
   *     data. Defaults to the current connection.
   *   - table: (optional) The name of the SQL table to use. Defaults to
   *     key_value_expire.
   */
  public function __construct($collection, Connection $connection, $table = 'key_value_expire') {
    parent::__construct($collection, $connection, $table);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
   */
  public function getMultiple(array $keys) {
    $values = $this->connection->query(
      'SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE expire > :now AND name IN (:keys) AND collection = :collection',
      array(
        ':now' => REQUEST_TIME,
        ':keys' => $keys,
        ':collection' => $this->collection,
      ))->fetchAllKeyed();
    return array_map('unserialize', $values);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getAll().
   */
  public function getAll() {
    $values = $this->connection->query(
      'SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection AND expire > :now',
      array(
        ':collection' => $this->collection,
        ':now' => REQUEST_TIME
      ))->fetchAllKeyed();
    return array_map('unserialize', $values);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpireInterface::setWithExpire().
   */
  function setWithExpire($key, $value, $expire) {
    // We are already writing to the table, so perform garbage collection at
    // the end of this request.
    $this->needsGarbageCollection = TRUE;
    $this->connection->merge($this->table)
      ->keys(array(
        'name' => $key,
        'collection' => $this->collection,
      ))
      ->fields(array(
        'value' => serialize($value),
        'expire' => REQUEST_TIME + $expire,
      ))
      ->execute();
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface::setWithExpireIfNotExists().
   */
  function setWithExpireIfNotExists($key, $value, $expire) {
    // We are already writing to the table, so perform garbage collection at
    // the end of this request.
    $this->needsGarbageCollection = TRUE;
    $result = $this->connection->merge($this->table)
      ->insertFields(array(
        'collection' => $this->collection,
        'name' => $key,
        'value' => serialize($value),
        'expire' => REQUEST_TIME + $expire,
      ))
      ->condition('collection', $this->collection)
      ->condition('name', $key)
      ->execute();
    return $result == Merge::STATUS_INSERT;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirablInterface::setMultipleWithExpire().
   */
  function setMultipleWithExpire(array $data, $expire) {
    foreach ($data as $key => $value) {
      $this->setWithExpire($key, $value, $expire);
    }
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) {
    // We are already writing to the table, so perform garbage collection at
    // the end of this request.
    $this->needsGarbageCollection = TRUE;
    parent::deleteMultiple($keys);
  }

  /**
   * Deletes expired items.
   */
  protected function garbageCollection() {
    $this->connection->delete($this->table)
      ->condition('expire', REQUEST_TIME, '<')
      ->execute();
  }

  /**
   * Implements Drupal\Core\DestructableInterface::destruct().
   */
  public function destruct() {
    if ($this->needsGarbageCollection) {
      $this->garbageCollection();
    }
  }

}
