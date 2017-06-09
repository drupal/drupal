<?php

namespace Drupal\Core\State;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Provides the state system using a key value store.
 */
class State extends CacheCollector implements StateInterface {

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Constructs a State object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, CacheBackendInterface $cache, LockBackendInterface $lock) {
    parent::__construct('state', $cache, $lock);
    $this->keyValueStore = $key_value_factory->get('state');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $value = parent::get($key);
    return $value !== NULL ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $value = $this->keyValueStore->get($key);
    $this->storage[$key] = $value;
    $this->persist($key);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $values = [];
    foreach ($keys as $key) {
      $values[$key] = $this->get($key);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    parent::set($key, $value);
    $this->keyValueStore->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      parent::set($key, $value);
    }
    $this->keyValueStore->setMultiple($data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    parent::delete($key);
    $this->keyValueStore->delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      parent::delete($key);
    }
    $this->keyValueStore->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->clear();
  }

}
