<?php

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
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    return array_intersect_key($this->data, array_flip($keys));
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    if (!isset($this->data[$key])) {
      $this->data[$key] = $value;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function delete($key) {
    unset($this->data[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      unset($this->data[$key]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->data = array();
  }

}
