<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueMemoryFactory.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueMemoryFactory {

  /**
   * An array of keyvalue collections that are stored in memory.
   *
   * @var array
   */
  protected $collections = array();

  /**
   * Constructs a new key/value memory storage object for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @return \Drupal\Core\KeyValueStore\MemoryStorage
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    if (!isset($this->collections[$collection])) {
      $this->collections[$collection] = new MemoryStorage($collection);
    }
    return $this->collections[$collection];
  }
}
