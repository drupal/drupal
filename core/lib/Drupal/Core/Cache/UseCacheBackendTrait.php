<?php

namespace Drupal\Core\Cache;

/**
 * Provides methods to use a cache backend while respecting a 'use caches' flag.
 */
trait UseCacheBackendTrait {

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Flag whether caches should be used or skipped.
   *
   * @var bool
   */
  protected $useCaches = TRUE;

  /**
   * Fetches from the cache backend, respecting the use caches flag.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  protected function cacheGet($cid) {
    if ($this->useCaches && $this->cacheBackend) {
      return $this->cacheBackend->get($cid);
    }
    return FALSE;
  }

  /**
   * Stores data in the persistent cache, respecting the use caches flag.
   *
   * @param string $cid
   *   The cache ID of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   *   Some storage engines only allow objects up to a maximum of 1MB in size to
   *   be stored by default. When caching large arrays or similar, take care to
   *   ensure $data does not exceed this size.
   * @param int $expire
   *   One of the following values:
   *   - CacheBackendInterface::CACHE_PERMANENT: Indicates that the item should
   *     not be removed unless it is deleted explicitly.
   *   - A Unix timestamp: Indicates that the item will be considered invalid
   *     after this time, i.e. it will not be returned by get() unless
   *     $allow_invalid has been set to TRUE. When the item has expired, it may
   *     be permanently deleted by the garbage collector at any time.
   * @param array $tags
   *   An array of tags to be stored with the cache item. These should normally
   *   identify objects used to build the cache item, which should trigger
   *   cache invalidation when updated. For example if a cached item represents
   *   a node, both the node ID and the author's user ID might be passed in as
   *   tags. For example, ['node' => [123], 'user' => [92]].
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   */
  protected function cacheSet($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    if ($this->cacheBackend && $this->useCaches) {
      $this->cacheBackend->set($cid, $data, $expire, $tags);
    }
  }

}
