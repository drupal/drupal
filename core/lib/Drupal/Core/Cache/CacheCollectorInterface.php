<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheCollectorInterface.
 */

namespace Drupal\Core\Cache;

/**
 * Provides a caching wrapper to be used in place of large structures.
 *
 * This should be extended by systems that need to cache large amounts of data
 * to calling functions. These structures can become very large, so this
 * class is used to allow different strategies to be used for caching internally
 * (lazy loading, building caches over time etc.). This can dramatically reduce
 * the amount of data that needs to be loaded from cache backends on each
 * request, and memory usage from static caches of that same data.
 *
 * The default implementation is \Drupal\Core\Cache\CacheCollector.
 */
interface CacheCollectorInterface {

  /**
   * Gets value from the cache.
   *
   * @param string $key
   *   Key that identifies the data.
   *
   * @return mixed
   *   The corresponding cache data.
   */
  public function get($key);

  /**
   * Sets cache data.
   *
   * It depends on the specific case and implementation whether this has a
   * permanent effect or if it just affects the current request.
   *
   * @param string $key
   *   Key that identifies the data.
   * @param mixed $value
   *   The data to be set.
   */
  public function set($key, $value);

  /**
   * Deletes the element.
   *
   * It depends on the specific case and implementation whether this has a
   * permanent effect or if it just affects the current request.
   *
   * @param string $key
   *   Key that identifies the data.
   */
  public function delete($key);

  /**
   * Returns whether data exists for this key.
   *
   * @param string $key
   *   Key that identifies the data.
   */
  public function has($key);

  /**
   * Resets the local cache.
   *
   * Does not clear the persistent cache.
   */
  public function reset();

  /**
   * Clears the collected cache entry.
   */
  public function clear();

}
