<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\MemoryStorage.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines a default key/value store implementation.
 *
 * For performance reasons, this implementation is not based on AbstractStorage.
 */
class MemoryStorage implements KeyValueStoreInterface {

  /**
   * The actual storage of key-value pairs.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::__construct().
   */
  public function __construct($collection, array $options = array()) {
    $this->collection = $collection;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getCollectionName().
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::get().
   */
  public function get($key) {
    return array_key_exists($key, $this->data) ? $this->data[$key] : FALSE;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
   */
  public function getMultiple(array $keys) {
    return array_intersect_key($this->data, array_flip($keys));
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getAll().
   */
  public function getAll() {
    return $this->data;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::set().
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setMultiple().
   */
  public function setMultiple(array $data) {
    $this->data = $data + $this->data;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::delete().
   */
  public function delete($key) {
    unset($this->data[$key]);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      unset($this->data[$key]);
    }
  }

}
