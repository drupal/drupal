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
   * Information about all deprecated state, keyed by legacy state key.
   *
   * Each entry should be an array that defines the following keys:
   *   - 'replacement': The new name for the state.
   *   - 'message': The deprecation message to use for trigger_error().
   *
   * @var array
   */
  private static array $deprecatedState = [];

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Tracks keys that have been modified during the request lifecycle.
   *
   * An associative array keyed by the state key name, where each value
   * is an array with the following keys:
   *   - value: The last value set during the request.
   *   - original: The initial value at the start of the request.
   */
  protected array $keysSetDuringRequest = [];

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
    // If the caller is asking for the value of a deprecated state, trigger a
    // deprecation message about it.
    if (isset(self::$deprecatedState[$key])) {
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error(self::$deprecatedState[$key]['message'], E_USER_DEPRECATED);
      $key = self::$deprecatedState[$key]['replacement'];
    }
    return parent::get($key) ?? $default;
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
    $this->setMultiple([$key => $value]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    $lock_name = $this->getCid() . ':' . CacheCollector::class;
    $lock_acquired = $this->lock->acquire($lock_name);
    $this->lazyLoadCache();
    foreach ($data as $key => $value) {
      $this->registerKeySetDuringRequest($key, $value, parent::get($key));
      if (isset(self::$deprecatedState[$key])) {
        // phpcs:ignore Drupal.Semantics.FunctionTriggerError
        @trigger_error(self::$deprecatedState[$key]['message'], E_USER_DEPRECATED);
        $data[self::$deprecatedState[$key]['replacement']] = $value;
        unset($data[$key]);
      }
    }
    $this->keyValueStore->setMultiple($data);
    // If another request had a cache miss before this request, and also hasn't
    // written to cache yet, then it may already have read the previous value
    // from the database and could write it to the cache at the end of the
    // request. To avoid this race condition, attempt to acquire a lock and
    // write to the cache immediately. This allows the race condition detection
    // in CacheCollector::updateCache() to work. We write to the cache whether
    // or not we acquire the lock, because CacheCollector::updateCache() handles
    // the case where there was no cache item at the beginning of the request,
    // but one was written by another request before ::updateCache() is called
    // - the new cache item functions as a tombstone record in this case.
    foreach ($data as $key => $value) {
      $this->storage[$key] = $value;
      // The key might have been marked for deletion.
      unset($this->keysToRemove[$key]);
      $this->persist($key);
    }
    if (!$lock_acquired) {
      // If we were unable to acquire a lock, immediately write the cache item
      // anyway. This acts as a tombstone for other requests that have not
      // reached a cache write yet: writing an (empty) item makes a concurrent
      // request that loaded a different item - or no item - detect the change
      // by content in ::updateCache() and back out. It also ensures that the
      // end of this request will detect that the cache item has changed.
      $this->cache->set($this->getCid(), [], CacheBackendInterface::CACHE_PERMANENT, $this->tags);
      $this->cacheInvalidated = TRUE;
      // Wait for the lock to become available for a maximum of one second, then
      // attempt to acquire the lock again. If we can't acquire the lock, then
      // the one second that has passed should have given most processes that
      // were in progress time to complete anyway.
      $this->lock->wait($lock_name, 1);
      $lock_acquired = $this->lock->acquire($lock_name);
      // If we were unable to acquire the lock even after waiting, write the
      // cache item a second time, this will override any cache writes in the
      // interim.
      if (!$lock_acquired) {
        $this->cache->set($this->getCid(), [], CacheBackendInterface::CACHE_PERMANENT, $this->tags);
      }
    }
    if ($lock_acquired) {
      // Write the new value within the lock. A concurrent request that read an
      // earlier cache item will detect the change when it compares the data
      // fingerprint in CacheCollector::updateCache(), even if both writes
      // happened within the same millisecond. This is why no sleep is needed to
      // force distinct cache item creation timestamps.
      // @see \Drupal\Core\Cache\CacheCollector::updateCache()
      $this->cache->set($this->getCid(), $data, CacheBackendInterface::CACHE_PERMANENT, $this->tags);

      // Because we've updated the cache within a lock here, we don't need to do
      // so again at the end of the request. Other requests can safely start
      // rebuilding the cache after this point.
      $this->lock->release($lock_name);
      $this->cacheInvalidated = FALSE;
      $this->keysToPersist = [];
      $this->keysToRemove = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->keyValueStore->delete($key);
    parent::delete($key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    $this->keyValueStore->deleteMultiple($keys);
    foreach ($keys as $key) {
      parent::delete($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function getValuesSetDuringRequest(string $key): ?array {
    return $this->keysSetDuringRequest[$key] ?? NULL;
  }

  /**
   * Registers a key that was set during the request.
   *
   * @param string $key
   *   The key that was set.
   * @param mixed $value
   *   The value that was set.
   * @param mixed $previousValue
   *   The previous value that was stored.
   */
  protected function registerKeySetDuringRequest(string $key, mixed $value, mixed $previousValue): void {
    $this->keysSetDuringRequest[$key]['value'] = $value;
    if (!array_key_exists('original', $this->keysSetDuringRequest[$key])) {
      $this->keysSetDuringRequest[$key]['original'] = $previousValue;
    }
  }

}
