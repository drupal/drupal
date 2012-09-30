<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\DatabaseStorage.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines a default key/value store implementation.
 *
 * This is Drupal's default key/value store implementation. It uses the database
 * to store key/value data.
 */
class DatabaseStorage extends StorageBase {

  /**
   * Overrides Drupal\Core\KeyValueStore\StorageBase::__construct().
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param array $options
   *   An associative array of options for the key/value storage collection.
   *   Keys used:
   *   - table. The name of the SQL table to use, defaults to key_value.
   */
  public function __construct($collection, array $options = array()) {
    parent::__construct($collection, $options);
    $this->table = isset($options['table']) ? $options['table'] : 'key_value';
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
   */
  public function getMultiple(array $keys) {
    $values = array();
    try {
      $result = db_query('SELECT name, value FROM {' . db_escape_table($this->table) . '} WHERE name IN (:keys) AND collection = :collection', array(':keys' => $keys, ':collection' => $this->collection))->fetchAllAssoc('name');
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
    $result = db_query('SELECT name, value FROM {' . db_escape_table($this->table) . '} WHERE collection = :collection', array(':collection' => $this->collection));
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
    db_merge($this->table)
      ->key(array(
        'name' => $key,
        'collection' => $this->collection,
      ))
      ->fields(array('value' => serialize($value)))
      ->execute();
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) {
    // Delete in chunks when a large array is passed.
    do {
      db_delete($this->table)
        ->condition('name', array_splice($keys, 0, 1000))
        ->condition('collection', $this->collection)
        ->execute();
    }
    while (count($keys));
  }

}
