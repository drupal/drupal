<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\KeyValueNullExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory for the null backend.
 */
class KeyValueNullExpirableFactory {

  /**
   * Constructs a new key/value expirable null storage object for a given
   * collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\DatabaseStorageExpirable
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    return new NullStorageExpirable($collection);
  }
}
