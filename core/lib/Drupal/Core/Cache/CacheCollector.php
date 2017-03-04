<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Default implementation for CacheCollectorInterface.
 *
 * By default, the class accounts for caches where calling functions might
 * request keys that won't exist even after a cache rebuild. This prevents
 * situations where a cache rebuild would be triggered over and over due to a
 * 'missing' item. These cases are stored internally as a value of NULL. This
 * means that the CacheCollector::get() method must be overridden if caching
 * data where the values can legitimately be NULL, and where
 * CacheCollector->has() needs to correctly return (equivalent to
 * array_key_exists() vs. isset()). This should not be necessary in the majority
 * of cases.
 *
 * @ingroup cache
 */
abstract class CacheCollector implements CacheCollectorInterface, DestructableInterface {

  /**
   * The cache id that is used for the cache entry.
   *
   * @var string
   */
  protected $cid;

  /**
   * A list of tags that are used for the cache entry.
   *
   * @var array
   */
  protected $tags;

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * An array of keys to add to the cache on service termination.
   *
   * @var array
   */
  protected $keysToPersist = [];

  /**
   * An array of keys to remove from the cache on service termination.
   *
   * @var array
   */
  protected $keysToRemove = [];

  /**
   * Storage for the data itself.
   *
   * @var array
   */
  protected $storage = [];

  /**
   * Stores the cache creation time.
   *
   * This is used to check if an invalidated cache item has been overwritten in
   * the meantime.
   *
   * @var int
   */
  protected $cacheCreated;

  /**
   * Flag that indicates of the cache has been invalidated.
   *
   * @var bool
   */
  protected $cacheInvalidated = FALSE;

  /**
   * Indicates if the collected cache was already loaded.
   *
   * The collected cache is lazy loaded when an entry is set, get or deleted.
   *
   * @var bool
   */
  protected $cacheLoaded = FALSE;

  /**
   * Constructs a CacheCollector object.
   *
   * @param string $cid
   *   The cid for the array being cached.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param array $tags
   *   (optional) The tags to specify for the cache item.
   */
  public function __construct($cid, CacheBackendInterface $cache, LockBackendInterface $lock, array $tags = []) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache tags must be strings.');
    $this->cid = $cid;
    $this->cache = $cache;
    $this->tags = $tags;
    $this->lock = $lock;
  }

  /**
   * Gets the cache ID.
   *
   * @return string
   */
  protected function getCid() {
    return $this->cid;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    // Make sure the value is loaded.
    $this->get($key);
    return isset($this->storage[$key]) || array_key_exists($key, $this->storage);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $this->lazyLoadCache();
    if (isset($this->storage[$key]) || array_key_exists($key, $this->storage)) {
      return $this->storage[$key];
    }
    else {
      return $this->resolveCacheMiss($key);
    }
  }

  /**
   * Implements \Drupal\Core\Cache\CacheCollectorInterface::set().
   *
   * This is not persisted by default. In practice this means that setting a
   * value will only apply while the object is in scope and will not be written
   * back to the persistent cache. This follows a similar pattern to static vs.
   * persistent caching in procedural code. Extending classes may wish to alter
   * this behavior, for example by adding a call to persist().
   */
  public function set($key, $value) {
    $this->lazyLoadCache();
    $this->storage[$key] = $value;
    // The key might have been marked for deletion.
    unset($this->keysToRemove[$key]);
    $this->invalidateCache();

  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->lazyLoadCache();
    unset($this->storage[$key]);
    $this->keysToRemove[$key] = $key;
    // The key might have been marked for persisting.
    unset($this->keysToPersist[$key]);
    $this->invalidateCache();
  }

  /**
   * Flags an offset value to be written to the persistent cache.
   *
   * @param string $key
   *   The key that was requested.
   * @param bool $persist
   *   (optional) Whether the offset should be persisted or not, defaults to
   *   TRUE. When called with $persist = FALSE the offset will be unflagged so
   *   that it will not be written at the end of the request.
   */
  protected function persist($key, $persist = TRUE) {
    $this->keysToPersist[$key] = $persist;
  }

  /**
   * Resolves a cache miss.
   *
   * When an offset is not found in the object, this is treated as a cache
   * miss. This method allows classes using this implementation to look up the
   * actual value and allow it to be cached.
   *
   * @param string $key
   *   The offset that was requested.
   *
   * @return mixed
   *   The value of the offset, or NULL if no value was found.
   */
  abstract protected function resolveCacheMiss($key);

  /**
   * Writes a value to the persistent cache immediately.
   *
   * @param bool $lock
   *   (optional) Whether to acquire a lock before writing to cache. Defaults to
   *   TRUE.
   */
  protected function updateCache($lock = TRUE) {
    $data = [];
    foreach ($this->keysToPersist as $offset => $persist) {
      if ($persist) {
        $data[$offset] = $this->storage[$offset];
      }
    }
    if (empty($data) && empty($this->keysToRemove)) {
      return;
    }

    // Lock cache writes to help avoid stampedes.
    $cid = $this->getCid();
    $lock_name = $this->normalizeLockName($cid . ':' . __CLASS__);
    if (!$lock || $this->lock->acquire($lock_name)) {
      // Set and delete operations invalidate the cache item. Try to also load
      // an eventually invalidated cache entry, only update an invalidated cache
      // entry if the creation date did not change as this could result in an
      // inconsistent cache.
      if ($cache = $this->cache->get($cid, $this->cacheInvalidated)) {
        if ($this->cacheInvalidated && $cache->created != $this->cacheCreated) {
          // We have invalidated the cache in this request and got a different
          // cache entry. Do not attempt to overwrite data that might have been
          // changed in a different request. We'll let the cache rebuild in
          // later requests.
          $this->cache->delete($cid);
          $this->lock->release($lock_name);
          return;
        }
        $data = array_merge($cache->data, $data);
      }
      // Remove keys marked for deletion.
      foreach ($this->keysToRemove as $delete_key) {
        unset($data[$delete_key]);
      }
      $this->cache->set($cid, $data, Cache::PERMANENT, $this->tags);
      if ($lock) {
        $this->lock->release($lock_name);
      }
    }

    $this->keysToPersist = [];
    $this->keysToRemove = [];
  }

  /**
   * Normalizes a cache ID in order to comply with database limitations.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An ASCII-encoded cache ID that is at most 255 characters long.
   */
  protected function normalizeLockName($cid) {
    // Nothing to do if the ID is a US ASCII string of 255 characters or less.
    $cid_is_ascii = mb_check_encoding($cid, 'ASCII');
    if (strlen($cid) <= 255 && $cid_is_ascii) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    if (!$cid_is_ascii) {
      return $hash;
    }
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->storage = [];
    $this->keysToPersist = [];
    $this->keysToRemove = [];
    $this->cacheLoaded = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $this->reset();
    if ($this->tags) {
      Cache::invalidateTags($this->tags);
    }
    else {
      $this->cache->delete($this->getCid());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    $this->updateCache();
  }

  /**
   * Loads the cache if not already done.
   */
  protected function lazyLoadCache() {
    if ($this->cacheLoaded) {
      return;
    }
    // The cache was not yet loaded, set flag to TRUE.
    $this->cacheLoaded = TRUE;

    if ($cache = $this->cache->get($this->getCid())) {
      $this->cacheCreated = $cache->created;
      $this->storage = $cache->data;
    }
  }

  /**
   * Invalidate the cache.
   */
  protected function invalidateCache() {
    // Invalidate the cache to make sure that other requests immediately see the
    // deletion before this request is terminated.
    $this->cache->invalidate($this->getCid());
    $this->cacheInvalidated = TRUE;
  }

}
