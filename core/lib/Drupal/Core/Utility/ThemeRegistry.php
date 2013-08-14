<?php

/**
 * @file
 * Definition of Drupal\Core\Utility\ThemeRegistry
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Builds the run-time theme registry.
 *
 * Extends CacheArray to allow the theme registry to be accessed as a
 * complete registry, while internally caching only the parts of the registry
 * that are actually in use on the site. On cache misses the complete
 * theme registry is loaded and used to update the run-time cache.
 */
class ThemeRegistry extends CacheArray {

  /**
   * Whether the partial registry can be persisted to the cache.
   *
   * This is only allowed if all modules and the request method is GET. theme()
   * should be very rarely called on POST requests and this avoids polluting
   * the runtime cache.
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
   * @param string $bin
   *   The bin to cache the array.
   * @param array $tags
   *   (optional) The tags to specify for the cache item.
   * @param bool $modules_loaded
   *   Whether all modules have already been loaded.
   */
  function __construct($cid, $bin, $tags, $modules_loaded = FALSE) {
    $this->cid = $cid;
    $this->bin = $bin;
    $this->tags = $tags;
    $this->persistable = $modules_loaded && $_SERVER['REQUEST_METHOD'] == 'GET';

    if ($this->persistable && $cached = cache($this->bin)->get($this->cid)) {
      $data = $cached->data;
    }
    else {
      // If there is no runtime cache stored, fetch the full theme registry,
      // but then initialize each value to NULL. This allows offsetExists()
      // to function correctly on non-registered theme hooks without triggering
      // a call to resolveCacheMiss().
      $data = $this->initializeRegistry();
      if ($this->persistable) {
        $this->set($data);
      }
    }
    $this->storage = $data;
  }

  /**
   * Initializes the full theme registry.
   *
   * @return
   *   An array with the keys of the full theme registry, but the values
   *   initialized to NULL.
   */
  function initializeRegistry() {
    $this->completeRegistry = theme_get_registry();

    return array_fill_keys(array_keys($this->completeRegistry), NULL);
  }

  /**
   * Overrides CacheArray::offsetExists().
   */
  public function offsetExists($offset) {
    // Since the theme registry allows for theme hooks to be requested that
    // are not registered, just check the existence of the key in the registry.
    // Use array_key_exists() here since a NULL value indicates that the theme
    // hook exists but has not yet been requested.
    return array_key_exists($offset, $this->storage);
  }

  /**
   * Overrides CacheArray::offsetGet().
   */
  public function offsetGet($offset) {
    // If the offset is set but empty, it is a registered theme hook that has
    // not yet been requested. Offsets that do not exist at all were not
    // registered in hook_theme().
    if (isset($this->storage[$offset])) {
      return $this->storage[$offset];
    }
    elseif (array_key_exists($offset, $this->storage)) {
      return $this->resolveCacheMiss($offset);
    }
  }

  /**
   * Implements CacheArray::resolveCacheMiss().
   */
  public function resolveCacheMiss($offset) {
    if (!isset($this->completeRegistry)) {
      $this->completeRegistry = theme_get_registry();
    }
    $this->storage[$offset] = $this->completeRegistry[$offset];
    if ($this->persistable) {
      $this->persist($offset);
    }
    return $this->storage[$offset];
  }

  /**
   * Overrides CacheArray::set().
   */
  public function set($data, $lock = TRUE) {
    $lock_name = $this->cid . ':' . $this->bin;
    if (!$lock || lock()->acquire($lock_name)) {
      if ($cached = cache($this->bin)->get($this->cid)) {
        // Use array merge instead of union so that filled in values in $data
        // overwrite empty values in the current cache.
        $data = array_merge($cached->data, $data);
      }
      else {
        $registry = $this->initializeRegistry();
        $data = array_merge($registry, $data);
      }
      cache($this->bin)->set($this->cid, $data, CacheBackendInterface::CACHE_PERMANENT, $this->tags);
      if ($lock) {
        lock()->release($lock_name);
      }
    }
  }
}
