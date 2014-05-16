<?php

/**
 * @file
 * Contains Drupal\Core\Config\CachedStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * Defines the cached storage.
 *
 * The class gets another storage and the cache factory injected. It reads from
 * the cache and delegates the read to the storage on a cache miss. It also
 * handles cache invalidation.
 */
class CachedStorage implements StorageInterface, StorageCacheInterface {

  /**
   * The configuration storage to be cached.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * The instantiated Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * List of listAll() prefixes with their results.
   *
   * @var array
   */
  protected $findByPrefixCache = array();

  /**
   * Constructs a new CachedStorage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage to be cached.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   A cache factory used for getting cache backends.
   */
  public function __construct(StorageInterface $storage, CacheFactoryInterface $cache_factory) {
    $this->storage = $storage;
    $this->cacheFactory = $cache_factory;
    $collection = $this->getCollectionName();
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      $bin = 'config';
    }
    else {
      $bin = 'config_' . str_replace('.', '_', $collection);
    }
    $this->cache = $this->cacheFactory->get($bin);
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
      // The cache contains either the cached configuration data or FALSE
      // if the configuration file does not exist.
      return $cache->data;
    }
    // Read from the storage on a cache miss and cache the data. Also cache
    // information about missing configuration objects.
    $data = $this->storage->read($name);
    $this->cache->set($name, $data);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = array();
    // The names array is passed by reference and will only contain the names of
    // config object not found after the method call.
    // @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
    $cached_list = $this->cache->getMultiple($names);

    if (!empty($names)) {
      $list = $this->storage->readMultiple($names);
      // Cache configuration objects that were loaded from the storage, cache
      // missing configuration objects as an explicit FALSE.
      $items = array();
      foreach ($names as $name) {
        $items[$name] = array('data' => isset($list[$name]) ? $list[$name] : FALSE);
      }

      $this->cache->setMultiple($items);
    }

    // Add the configuration objects from the cache to the list.
    foreach ($cached_list as $name => $cache) {
      $list[$name] = $cache->data;
    }

    // Ensure that only existing configuration objects are returned, filter out
    // cached information about missing objects.
    return array_filter($list);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::write().
   */
  public function write($name, array $data) {
    if ($this->storage->write($name, $data)) {
      // While not all written data is read back, setting the cache instead of
      // just deleting it avoids cache rebuild stampedes.
      $this->cache->set($name, $data);
      Cache::deleteTags(array($this::FIND_BY_PREFIX_CACHE_TAG => TRUE));
      $this->findByPrefixCache = array();
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
      Cache::deleteTags(array($this::FIND_BY_PREFIX_CACHE_TAG => TRUE));
      $this->findByPrefixCache = array();
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
      Cache::deleteTags(array($this::FIND_BY_PREFIX_CACHE_TAG => TRUE));
      $this->findByPrefixCache = array();
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
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    // Do not cache when a prefix is not provided.
    if ($prefix) {
      return $this->findByPrefix($prefix);
    }
    return $this->storage->listAll();
  }

  /**
   * Finds configuration object names starting with a given prefix.
   *
   * Given the following configuration objects:
   * - node.type.article
   * - node.type.page
   *
   * Passing the prefix 'node.type.' will return an array containing the above
   * names.
   *
   * @param string $prefix
   *   The prefix to search for
   *
   * @return array
   *   An array containing matching configuration object names.
   */
  protected function findByPrefix($prefix) {
    if (!isset($this->findByPrefixCache[$prefix])) {
      // The : character is not allowed in config file names, so this can not
      // conflict.
      if ($cache = $this->cache->get('find:' . $prefix)) {
        $this->findByPrefixCache[$prefix] = $cache->data;
      }
      else {
        $this->findByPrefixCache[$prefix] = $this->storage->listAll($prefix);
        $this->cache->set(
          'find:' . $prefix,
          $this->findByPrefixCache[$prefix],
          Cache::PERMANENT,
          array($this::FIND_BY_PREFIX_CACHE_TAG => TRUE)
        );
      }
    }
    return $this->findByPrefixCache[$prefix];
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

  /**
   * Clears the static list cache.
   */
  public function resetListCache() {
    $this->findByPrefixCache = array();
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->storage->createCollection($collection),
      $this->cacheFactory
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
    return $this->storage->getCollectionName();
  }

}
