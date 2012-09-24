<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\AbstractStorage.
 */

namespace Drupal\Core\KeyValueStore;

abstract class AbstractStorage implements KeyValueStoreInterface {

  /**
   * The name of the collection holding key and value pairs.
   *
   * @var string
   */
  protected $collection;

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
    $values = $this->getMultiple(array($key));
    return isset($values[$key]) ? $values[$key] : NULL;
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
