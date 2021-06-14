<?php

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
  protected $data = [];

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
   * {@inheritdoc}
   */
  public function has($key) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {}

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {}

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {}

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {}

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {}

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {}

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function setMultipleWithExpire(array $data, $expire) {}

  /**
   * {@inheritdoc}
   */
  public function setWithExpire($key, $value, $expire) {}

  /**
   * {@inheritdoc}
   */
  public function setWithExpireIfNotExists($key, $value, $expire) {}

}
