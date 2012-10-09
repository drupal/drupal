<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueFactory.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory.
 */
class KeyValueFactory {

  /**
   * Instantiated stores, keyed by collection name.
   *
   * @var array
   */
  protected $stores = array();

  /**
   * Constructs a new key/value store for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return Drupal\Core\KeyValueStore\DatabaseStorage
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    if (!isset($this->stores[$collection])) {
      $this->stores[$collection] = new DatabaseStorage($collection);
    }
    return $this->stores[$collection];
  }
}
