<?php

namespace Drupal\Core\State;

/**
 * Defines the interface for the state system.
 *
 * @ingroup state_api
 */
interface StateInterface {

  /**
   * Returns the stored value for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function get($key, $default = NULL);

  /**
   * Returns the stored key/value pairs for a given set of keys.
   *
   * @param array $keys
   *   A list of keys to retrieve.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function getMultiple(array $keys);

  /**
   * Saves a value for a given key.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function set($key, $value);

  /**
   * Saves key/value pairs.
   *
   * @param array $data
   *   An associative array of key/value pairs.
   */
  public function setMultiple(array $data);

  /**
   * Deletes an item.
   *
   * @param string $key
   *   The item name to delete.
   */
  public function delete($key);

  /**
   * Deletes multiple items.
   *
   * @param array $keys
   *   A list of item names to delete.
   */
  public function deleteMultiple(array $keys);

  /**
   * Resets the static cache.
   *
   * This is mainly used in testing environments.
   */
  public function resetCache();

}
