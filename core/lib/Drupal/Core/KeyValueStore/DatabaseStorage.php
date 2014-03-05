<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\DatabaseStorage.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Connection;

/**
 * Defines a default key/value store implementation.
 *
 * This is Drupal's default key/value store implementation. It uses the database
 * to store key/value data.
 */
class DatabaseStorage extends StorageBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table to use.
   *
   * @var string
   */
  protected $table;

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param string $table
   *   The name of the SQL table to use, defaults to key_value.
   */
  public function __construct($collection, Connection $connection, $table = 'key_value') {
    parent::__construct($collection);
    $this->connection = $connection;
    $this->table = $table;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
   */
  public function getMultiple(array $keys) {
    $values = array();
    try {
      $result = $this->connection->query('SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name IN (:keys) AND collection = :collection', array(':keys' => $keys, ':collection' => $this->collection))->fetchAllAssoc('name');
      foreach ($keys as $key) {
        if (isset($result[$key])) {
          $values[$key] = unserialize($result[$key]->value);
        }
      }
    }
    catch (\Exception $e) {
      // @todo: Perhaps if the database is never going to be available,
      // key/value requests should return FALSE in order to allow exception
      // handling to occur but for now, keep it an array, always.
    }
    return $values;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getAll().
   */
  public function getAll() {
    $result = $this->connection->query('SELECT name, value FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection', array(':collection' => $this->collection));
    $values = array();

    foreach ($result as $item) {
      if ($item) {
        $values[$item->name] = unserialize($item->value);
      }
    }
    return $values;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::set().
   */
  public function set($key, $value) {
    $this->connection->merge($this->table)
      ->keys(array(
        'name' => $key,
        'collection' => $this->collection,
      ))
      ->fields(array('value' => serialize($value)))
      ->execute();
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setIfNotExists().
   */
  public function setIfNotExists($key, $value) {
    $result = $this->connection->merge($this->table)
      ->insertFields(array(
        'collection' => $this->collection,
        'name' => $key,
        'value' => serialize($value),
      ))
      ->condition('collection', $this->collection)
      ->condition('name', $key)
      ->execute();
    return $result == Merge::STATUS_INSERT;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) {
    // Delete in chunks when a large array is passed.
    do {
      $this->connection->delete($this->table)
        ->condition('name', array_splice($keys, 0, 1000))
        ->condition('collection', $this->collection)
        ->execute();
    }
    while (count($keys));
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteAll().
   */
  public function deleteAll() {
    $this->connection->delete($this->table)
      ->condition('collection', $this->collection)
      ->execute();
  }
}
