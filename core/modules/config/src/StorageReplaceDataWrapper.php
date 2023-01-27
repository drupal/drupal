<?php

namespace Drupal\config;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Wraps a configuration storage to allow replacing specific configuration data.
 */
class StorageReplaceDataWrapper implements StorageInterface {
  use DependencySerializationTrait;

  /**
   * The configuration storage to be wrapped.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The configuration replacement data, keyed by configuration object name.
   *
   * @var array
   */
  protected $replacementData = [];

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * Constructs a new StorageReplaceDataWrapper.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage to be used to read and write configuration.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct(StorageInterface $storage, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->storage = $storage;
    $this->collection = $collection;
    $this->replacementData[$collection] = [];
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return isset($this->replacementData[$this->collection][$name]) || $this->storage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    if (isset($this->replacementData[$this->collection][$name])) {
      return $this->replacementData[$this->collection][$name];
    }
    return $this->storage->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = $this->storage->readMultiple(($names));
    foreach ($names as $name) {
      if (isset($this->replacementData[$this->collection][$name])) {
        $data[$name] = $this->replacementData[$this->collection][$name];
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    if (isset($this->replacementData[$this->collection][$name])) {
      unset($this->replacementData[$this->collection][$name]);
    }
    return $this->storage->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (isset($this->replacementData[$this->collection][$name])) {
      unset($this->replacementData[$this->collection][$name]);
    }
    return $this->storage->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    if (isset($this->replacementData[$this->collection][$name])) {
      $this->replacementData[$this->collection][$new_name] = $this->replacementData[$this->collection][$name];
      unset($this->replacementData[$this->collection][$name]);
    }
    return $this->storage->rename($name, $new_name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->storage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->storage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $names = $this->storage->listAll($prefix);
    $additional_names = [];
    if ($prefix === '') {
      $additional_names = array_keys($this->replacementData[$this->collection]);
    }
    else {
      foreach (array_keys($this->replacementData[$this->collection]) as $name) {
        if (str_starts_with($name, $prefix)) {
          $additional_names[] = $name;
        }
      }
    }
    if (!empty($additional_names)) {
      $names = array_unique(array_merge($names, $additional_names));
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    if ($prefix === '') {
      $this->replacementData[$this->collection] = [];
    }
    else {
      foreach (array_keys($this->replacementData[$this->collection]) as $name) {
        if (str_starts_with($name, $prefix)) {
          unset($this->replacementData[$this->collection][$name]);
        }
      }
    }
    return $this->storage->deleteAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->storage->createCollection($collection),
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    return $this->storage->getAllCollectionNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Replaces the configuration object data with the supplied data.
   *
   * @param $name
   *   The configuration object name whose data to replace.
   * @param array $data
   *   The configuration data.
   *
   * @return $this
   */
  public function replaceData($name, array $data) {
    $this->replacementData[$this->collection][$name] = $data;
    return $this;
  }

}
