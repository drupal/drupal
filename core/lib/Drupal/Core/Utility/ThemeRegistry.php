<?php

namespace Drupal\Core\Utility;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Builds the run-time theme registry.
 *
 * A cache collector to allow the theme registry to be accessed as a
 * complete registry, while internally caching only the parts of the registry
 * that are actually in use on the site. On cache misses the complete
 * theme registry is loaded and used to update the run-time cache.
 */
class ThemeRegistry extends CacheCollector implements DestructableInterface {

  /**
   * Whether the partial registry can be persisted to the cache.
   *
   * This is only allowed if all modules and the request method is GET. _theme()
   * should be very rarely called on POST requests and this avoids polluting
   * the runtime cache.
   *
   * @var bool
   */
  protected $persistable;

  /**
   * The complete theme registry array.
   */
  protected $completeRegistry;

  /**
   * Constructs a ThemeRegistry object.
   *
   * @param string $cid
   *   The cid for the array being cached.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param array $tags
   *   (optional) The tags to specify for the cache item.
   * @param bool $modules_loaded
   *   Whether all modules have already been loaded.
   */
  public function __construct($cid, CacheBackendInterface $cache, LockBackendInterface $lock, $tags = [], $modules_loaded = FALSE) {
    $this->cid = $cid;
    $this->cache = $cache;
    $this->lock = $lock;
    $this->tags = $tags;
    $this->persistable = $modules_loaded && \Drupal::hasRequest() && \Drupal::request()->isMethod('GET');

    // @todo: Implement lazy-loading.
    $this->cacheLoaded = TRUE;

    if ($this->persistable && $cached = $this->cache->get($this->cid)) {
      $this->storage = $cached->data;
    }
    else {
      // If there is no runtime cache stored, fetch the full theme registry,
      // but then initialize each value to NULL. This allows offsetExists()
      // to function correctly on non-registered theme hooks without triggering
      // a call to resolveCacheMiss().
      $this->storage = $this->initializeRegistry();
      foreach (array_keys($this->storage) as $key) {
        $this->persist($key);
      }
      // RegistryTest::testRaceCondition() ensures that the cache entry is
      // written on the initial construction of the theme registry.
      $this->updateCache();
    }
  }

  /**
   * Initializes the full theme registry.
   *
   * @return array
   *   An array with the keys of the full theme registry, but the values
   *   initialized to NULL.
   */
  public function initializeRegistry() {
    // @todo DIC this.
    $this->completeRegistry = \Drupal::service('theme.registry')->get();

    return array_fill_keys(array_keys($this->completeRegistry), NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    // Since the theme registry allows for theme hooks to be requested that
    // are not registered, just check the existence of the key in the registry.
    // Use array_key_exists() here since a NULL value indicates that the theme
    // hook exists but has not yet been requested.
    return \array_key_exists($key, $this->storage);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    // If the offset is set but empty, it is a registered theme hook that has
    // not yet been requested. Offsets that do not exist at all were not
    // registered in hook_theme().
    if (isset($this->storage[$key])) {
      return $this->storage[$key];
    }
    elseif (array_key_exists($key, $this->storage)) {
      return $this->resolveCacheMiss($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resolveCacheMiss($key) {
    if (!isset($this->completeRegistry)) {
      $this->completeRegistry = \Drupal::service('theme.registry')->get();
    }
    $this->storage[$key] = $this->completeRegistry[$key];
    if ($this->persistable) {
      $this->persist($key);
    }
    return $this->storage[$key];
  }

  /**
   * {@inheritdoc}
   */
  protected function updateCache($lock = TRUE) {
    if (!$this->persistable) {
      return;
    }
    // @todo: Is the custom implementation necessary?
    $data = [];
    foreach ($this->keysToPersist as $offset => $persist) {
      if ($persist) {
        $data[$offset] = $this->storage[$offset];
      }
    }
    if (empty($data)) {
      return;
    }

    $lock_name = $this->cid . ':' . __CLASS__;
    if (!$lock || $this->lock->acquire($lock_name)) {
      if ($cached = $this->cache->get($this->cid)) {
        // Use array merge instead of union so that filled in values in $data
        // overwrite empty values in the current cache.
        $data = array_merge($cached->data, $data);
      }
      else {
        $registry = $this->initializeRegistry();
        $data = array_merge($registry, $data);
      }
      $this->cache->set($this->cid, $data, Cache::PERMANENT, $this->tags);
      if ($lock) {
        $this->lock->release($lock_name);
      }
    }
  }

}
