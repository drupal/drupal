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
    $cache_backends = cache_get_backends();
    $class = isset($cache_backends[$bin]) ? $cache_backends[$bin] : $cache_backends['cache'];
    $cache_objects[$bin] = new $class($bin);
  }
  return $cache_objects[$bin];
}

/**
 * Invalidates the items associated with given list of tags.
 *
 * Many sites have more than one active cache backend, and each backend my use
 * a different strategy for storing tags against cache items, and invalidating
 * cache items associated with a given tag.
 *
 * When invalidating a given list of tags, we iterate over each cache backend,
 * and call invalidate on each.
 *
 * @param array $tags
 *   The list of tags to invalidate cache items for.
 */
function cache_invalidate(array $tags) {
  foreach (cache_get_backends() as $bin => $class) {
    cache($bin)->invalidateTags($tags);
  }
}

/**
 * Returns a list of cache backends for this site.
 *
 * @return
 *   An associative array with cache bins as keys, and backend classes as value.
 */
function cache_get_backends() {
  return variable_get('cache_classes', array('cache' => 'Drupal\Core\Cache\DatabaseBackend'));
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
