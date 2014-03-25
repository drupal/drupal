<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\MemoryStorage.
 */

namespace Drupal\Core\KeyValueStore;

/**
 * Defines a default key/value store implementation.
 */
class MemoryStorage extends StorageBase {

  /**
   * The actual storage of key-value pairs.
   *
   * @var array
   */
  protected $data = array();

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return array_key_exists($key, $this->data);
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::get().
   */
  public function get($key, $default = NULL) {
    return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
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
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setIfNotExists().
   */
  public function setIfNotExists($key, $value) {
    if (!isset($this->data[$key])) {
      $this->data[$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::setMultiple().
   */
  public function setMultiple(array $data) {
    $this->data = $data + $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
    $this->data[$new_key] = $this->data[$key];
    unset($this->data[$key]);
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

  /**
   * Implements Drupal\Core\KeyValueStore\KeyValueStoreInterface::deleteAll().
   */
  public function deleteAll() {
    $this->data = array();
  }
}
