<?php

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the expirable key/value store factory interface.
 */
interface KeyValueExpirableFactoryInterface {

  /**
   * Constructs a new expirable key/value store for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   An expirable key/value store implementation for the given $collection.
   */
  public function get($collection);

}
