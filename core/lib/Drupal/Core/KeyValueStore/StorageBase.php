<?php

namespace Drupal\Core\KeyValueStore;

/**
 * Provides a base class for key/value storage implementations.
 */
abstract class StorageBase implements KeyValueStoreInterface {

  /**
   * {@inheritdoc}
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   */
  public function __construct(
    protected $collection,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $values = $this->getMultiple([$key]);
    return $values[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllKeys(): iterable {
    return array_keys($this->getAll());
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->deleteMultiple([$key]);
  }

}
