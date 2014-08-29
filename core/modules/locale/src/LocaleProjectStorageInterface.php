<?php

/**
 * @file
 * Contains Drupal\locale\LocalProjectStorageInterface.
 */

namespace Drupal\locale;

/**
 * Defines the locale project storage interface.
 */
interface LocaleProjectStorageInterface {

  /**
   * Returns the stored value for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or the default value if no value exists.
   */
  public function get($key, $default = NULL);

  /**
   * Returns a list of project records.
   *
   * @param array $keys
   *   A list of keys to retrieve.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function getMultiple(array $keys);

  /**
   * Creates or updates the project record.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function set($key, $value);

  /**
   * Creates or updates multiple project records.
   *
   * @param array $data
   *   An associative array of key/value pairs.
   */
  public function setMultiple(array $data);

  /**
   * Deletes project records for a given key.
   *
   * @param string $key
   *   The key of the data to delete.
   */
  public function delete($key);

  /**
   * Deletes multiple project records.
   *
   * @param array $keys
   *   A list of item names to delete.
   */
  public function deleteMultiple(array $keys);

  /**
   * Returns all the project records.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function getAll();

  /**
   * Deletes all projects records.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function deleteAll();

  /**
   * Mark all projects as disabled.
   */
  public function disableAll();

  /**
   * Resets the project storage cache.
   */
  public function resetCache();

  /**
   * Returns the count of project records.
   *
   * @return int
   *   The number of saved items.
   */
  public function countProjects();
}
