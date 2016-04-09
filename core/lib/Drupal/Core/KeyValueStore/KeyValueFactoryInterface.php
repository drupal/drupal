<?php

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory interface.
 */
interface KeyValueFactoryInterface {

  /**
   * Constructs a new key/value store for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection);

}

