<?php

/**
 * @file
 * Functions and interfaces for cache handling.
 */

use Drupal\Core\Cache\CacheFactory;

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
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__, array());
  }
  $cache_objects = &$drupal_static_fast['cache'];

  // Temporary backwards compatibiltiy layer, allow old style prefixed cache
  // bin names to be passed as arguments.
  $bin = str_replace('cache_', '', $bin);

  if (!isset($cache_objects[$bin])) {
    $cache_objects[$bin] = CacheFactory::get($bin);
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
  foreach (CacheFactory::getBackends() as $bin => $class) {
    cache($bin)->invalidateTags($tags);
  }
}
