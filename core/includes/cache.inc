<?php

/**
 * @file
 * Functions and interfaces for cache handling.
 */

/**
 * Instantiates and statically caches the correct class for a cache bin.
 *
 * By default, this returns an instance of the Drupal\Core\Cache\DatabaseBackend
 * class.
 *
 * Classes implementing Drupal\Core\Cache\CacheBackendInterface can register
 * themselves both as a default implementation and for specific bins.
 *
 * @param $bin
 *   The cache bin for which the cache object should be returned, defaults to
 *   'cache'.
 *
 * @return Drupal\Core\Cache\CacheBackendInterface
 *   The cache object associated with the specified bin.
 *
 * @see Drupal\Core\Cache\CacheBackendInterface
 */
function cache($bin = 'cache') {
  // Temporary backwards compatibiltiy layer, allow old style prefixed cache
  // bin names to be passed as arguments.
  $bin = str_replace('cache_', '', $bin);

  // We do not use drupal_static() here because we do not want to change the
  // storage of a cache bin mid-request.
  static $cache_objects;
  if (!isset($cache_objects[$bin])) {
    $class = variable_get('cache_class_' . $bin);
    if (!isset($class)) {
      $class = variable_get('cache_default_class', 'Drupal\Core\Cache\DatabaseBackend');
    }
    $cache_objects[$bin] = new $class($bin);
  }
  return $cache_objects[$bin];
}

/**
 * Expires data from the block and page caches.
 */
function cache_clear_all() {
  // @todo: remove before release.
  if (func_get_args()) {
    throw new Exception(t('cache_clear_all() no longer takes arguments, use cache() instead.'));
  }
  // Clear the block cache first, so stale data will
  // not end up in the page cache.
  if (module_exists('block')) {
    cache('block')->expire();
  }
  cache('page')->expire();
}
