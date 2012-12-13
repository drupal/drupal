<?php

/**
 * @file
 * Contains Drupal\Core\Config\CachedStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Defines the cached storage controller.
 *
 * The class gets another storage and a cache backend injected. It reads from
 * the cache and delegates the read to the storage on a cache miss. It also
 * handles cache invalidation.
 */
class CachedStorage implements StorageInterface {

  /**
   * The configuration storage to be cached.
   *
   * @var Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The instantiated Cache backend.
   *
   * @var Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new CachedStorage controller.
   *
   * @param Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage controller to be cached.
   * @param Drupal\Core\Cache\CacheBackendInterface $cache
   *   A cache backend instance to use for caching.
   */
  public function __construct(StorageInterface $storage, CacheBackendInterface $cache) {
    $this->storage = $storage;
    $this->cache = $cache;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    // The cache would read in the entire data (instead of only checking whether
    // any data exists), and on a potential cache miss, an additional storage
    // lookup would have to happen, so check the storage directly.
    return $this->storage->exists($name);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   */
  public function read($name) {
    if ($cache = $this->cache->get($name)) {
      // The cache backend supports primitive data types, but only an array
      // represents valid config object data.
      if (is_array($cache->data)) {
        return $cache->data;
      }
    }
    // Read from the storage on a cache miss and cache the data, if any.
    $data = $this->storage->read($name);
    if ($data !== FALSE) {
      $this->cache->set($name, $data, CacheBackendInterface::CACHE_PERMANENT);
    }
    // If the cache contained bogus data and there is no data in the storage,
    // wipe the cache entry.
    elseif ($cache) {
      $this->cache->delete($name);
    }
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   */
  public function write($name, array $data) {
    if ($this->storage->write($name, $data)) {
      // While not all written data is read back, setting the cache instead of
      // just deleting it avoids cache rebuild stampedes.
      $this->cache->set($name, $data, CacheBackendInterface::CACHE_PERMANENT);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    // If the cache was the first to be deleted, another process might start
    // rebuilding the cache before the storage is gone.
    if ($this->storage->delete($name)) {
      $this->cache->delete($name);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   */
  public function rename($name, $new_name) {
    // If the cache was the first to be deleted, another process might start
    // rebuilding the cache before the storage is renamed.
    if ($this->storage->rename($name, $new_name)) {
      $this->cache->delete($name);
      $this->cache->delete($new_name);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    return $this->storage->encode($data);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   */
  public function decode($raw) {
    return $this->storage->decode($raw);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   *
   * Not supported by CacheBackendInterface.
   */
  public function listAll($prefix = '') {
    return $this->storage->listAll($prefix);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
   */
  public function deleteAll($prefix = '') {
    // If the cache was the first to be deleted, another process might start
    // rebuilding the cache before the storage is renamed.
    $cids = $this->storage->listAll($prefix);
    if ($this->storage->deleteAll($prefix)) {
      $this->cache->deleteMultiple($cids);
      return TRUE;
    }
    return FALSE;
  }
}
