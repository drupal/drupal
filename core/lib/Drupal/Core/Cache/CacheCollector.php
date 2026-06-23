<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Assertion\Inspector;
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
   * @var int
   *
   * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. If checking
   * whether the cache item was loaded, you can check the loadedData property
   * instead.
   * @see https://www.drupal.org/project/drupal/issues/3496328
   */
  protected $cacheCreated;

  /**
   * The cache item's data as loaded during this request, or NULL if none.
   *
   * Retained so that ::updateCache() can fingerprint it to detect a concurrent
   * change. The fingerprint is computed lazily there, and only when there is
   * data to write back, so that requests that only read from the collector do
   * not pay for hashing.
   */
  protected ?array $loadedData = NULL;

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
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');
    $this->cid = $cid;
    $this->cache = $cache;
    $this->tags = $tags;
    $this->lock = $lock;
  }

  /**
   * Gets the cache ID.
   *
   * @return string
   *   The cache ID.
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
    return \array_key_exists($key, $this->storage);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $this->lazyLoadCache();
    if (\array_key_exists($key, $this->storage)) {
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
   * this behavior, for example by adding a call to persist(). If you are
   * writing data to somewhere in addition to the cache item in ::set(), you
   * should invalidate the cache item within a lock to ensure that another
   * request that starts with an empty cache item does not overwrite with the
   * previous value. For example: Drupal\Core\State\State.
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
   *   TRUE. When called with $persist = FALSE the offset will be un-flagged so
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

    // Lock cache writes to help avoid stampedes. Try to acquire the lock, but
    // continue even if it cannot be acquired: a ::set() or ::delete() during
    // this request may have invalidated the cache item, and that invalidation
    // must be propagated whether or not the lock is held. Only the cache write
    // itself depends on the lock.
    $cid = $this->getCid();
    $lock_name = $cid . ':' . __CLASS__;
    $lock_acquired = FALSE;
    if ($lock) {
      $lock_acquired = $this->lock->acquire($lock_name);
    }

    // Set and delete operations invalidate the cache item, so load an
    // invalidated cache entry too if this request invalidated it. Comparing
    // the content detects changes to the content; if it changed, another
    // request is responsible for it and this request must not overwrite that
    // work.
    $cache = $this->cache->get($cid, $this->cacheInvalidated);
    $fingerprint = $cache ? $this->fingerprint($cache->data) : NULL;
    $loaded_fingerprint = $this->loadedData === NULL ? NULL : $this->fingerprint($this->loadedData);
    $unchanged = $fingerprint === $loaded_fingerprint;

    if ($cache && $this->cacheInvalidated) {
      // This request invalidated the cache item via ::set() or ::delete() and a
      // cache item still exists. Delete it so that a later request rebuilds it
      // cleanly; the up-to-date value is written within a lock by the caller
      // (see ::set()). This happens whether or not the lock was acquired.
      $this->cache->delete($cid);
    }
    elseif ($unchanged && (!$lock || $lock_acquired)) {
      // The cache item is in the same state as when it was loaded, so it is
      // safe to write back the data collected during this request, merged with
      // any existing cache data and with deleted keys removed.
      if ($cache) {
        $data = array_merge($cache->data, $data);
      }
      foreach ($this->keysToRemove as $delete_key) {
        unset($data[$delete_key]);
      }
      $this->cache->set($cid, $data, Cache::PERMANENT, $this->tags);
    }

    if ($lock_acquired) {
      $this->lock->release($lock_name);
    }

    $this->keysToPersist = [];
    $this->keysToRemove = [];
  }

  /**
   * Computes a fingerprint of cache item data for change detection.
   *
   * @param array $data
   *   The cache item data.
   *
   * @return string
   *   A hash of the data. A concurrent write that changes the data produces a
   *   different fingerprint, even when it happens within the same millisecond.
   */
  protected function fingerprint(array $data): string {
    return hash('xxh128', serialize($data));
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->storage = [];
    $this->keysToPersist = [];
    $this->keysToRemove = [];
    $this->cacheLoaded = FALSE;
    $this->loadedData = NULL;
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
    $this->reset();
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
      // @phpstan-ignore property.deprecated
      $this->cacheCreated = $cache->created;
      $this->storage = $cache->data;
      // Retain the loaded data so that ::updateCache() can detect whether the
      // cache item changed during the request. The fingerprint is computed
      // lazily there, and only when there is data to write back, so that
      // requests that only read from the collector do not pay for hashing.
      $this->loadedData = $cache->data;
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
