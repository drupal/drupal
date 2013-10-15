<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\StorageBase.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Provides a base class for key/value storage implementations.
 */
abstract class StorageBase implements KeyValueStoreInterface {

  /**
   * The name of the collection holding key and value pairs.
   *
   * @var string
   */
  protected $collection;

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::__construct().
   */
  public function __construct($collection) {
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
  public function get($key, $default = NULL) {
    $values = $this->getMultiple(array($key));
    return isset($values[$key]) ? $values[$key] : $default;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setMultiple().
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::delete().
   */
  public function delete($key) {
    $this->deleteMultiple(array($key));
  }

}
