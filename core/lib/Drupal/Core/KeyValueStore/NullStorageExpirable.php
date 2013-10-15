<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\NullStorageExpirable.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines a null key/value store implementation.
 */
class NullStorageExpirable implements KeyValueStoreExpirableInterface {

  /**
   * The actual storage of key-value pairs.
   *
   * @var array
   */
  protected $data = array();

  /**
   * The name of the collection holding key and value pairs.
   *
   * @var string
   */
  protected $collection;

  /**
   * Creates a new expirable null key/value store.
   */
  public function __construct($collection) {
    $this->collection = $collection;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::get().
   */
  public function get($key, $default = NULL) {
    return NULL;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getMultiple().
   */
  public function getMultiple(array $keys) {
    return array();
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getAll().
   */
  public function getAll() {
    return array();
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::set().
   */
  public function set($key, $value) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setIfNotExists().
   */
  public function setIfNotExists($key, $value) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setMultiple().
   */
  public function setMultiple(array $data) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::delete().
   */
  public function delete($key) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteMultiple().
   */
  public function deleteMultiple(array $keys) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteAll().
   */
  public function deleteAll() { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::getCollectionName().
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface::setMultipleWithExpire().
   */
  public function setMultipleWithExpire(array $data, $expire) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface::setWithExpire().
   */
  public function setWithExpire($key, $value, $expire) { }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface::setWithExpireIfNotExists().
   */
  public function setWithExpireIfNotExists($key, $value, $expire) { }

}
