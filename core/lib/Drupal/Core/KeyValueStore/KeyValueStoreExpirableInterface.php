<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the interface for expiring data in a key/value store.
 */
interface KeyValueStoreExpirableInterface extends KeyValueStoreInterface {

  /**
   * Saves a value for a given key with a time to live.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function setWithExpire($key, $value, $expire);

  /**
   * Sets a value for a given key with a time to live if it does not yet exist.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   *
   * @return bool
   *   TRUE if the data was set, or FALSE if it already existed.
   */
  public function setWithExpireIfNotExists($key, $value, $expire);

  /**
   * Saves an array of values with a time to live.
   *
   * @param array $data
   *   An array of data to store.
   * @param int $expire
   *   The time to live for items, in seconds.
   */
  public function setMultipleWithExpire(array $data, $expire);

}
