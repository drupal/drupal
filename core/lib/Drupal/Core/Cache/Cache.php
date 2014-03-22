<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Cache.
 */

namespace Drupal\Core\Cache;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Helper methods for cache.
 *
 * @ingroup cache
 */
class Cache {

  /**
   * Indicates that the item should never be removed unless explicitly deleted.
   */
  const PERMANENT = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * Deletes items from all bins with any of the specified tags.
   *
   * Many sites have more than one active cache backend, and each backend may
   * use a different strategy for storing tags against cache items, and
   * deleting cache items associated with a given tag.
   *
   * When deleting a given list of tags, we iterate over each cache backend, and
   * and call deleteTags() on each.
   *
   * @param array $tags
   *   The list of tags to delete cache items for.
   */
  public static function deleteTags(array $tags) {
    foreach (static::getBins() as $cache_backend) {
      $cache_backend->deleteTags($tags);
    }
  }

  /**
   * Marks cache items from all bins with any of the specified tags as invalid.
   *
   * Many sites have more than one active cache backend, and each backend my use
   * a different strategy for storing tags against cache items, and invalidating
   * cache items associated with a given tag.
   *
   * When invalidating a given list of tags, we iterate over each cache backend,
   * and call invalidateTags() on each.
   *
   * @param array $tags
   *   The list of tags to invalidate cache items for.
   */
  public static function invalidateTags(array $tags) {
    foreach (static::getBins() as $cache_backend) {
      $cache_backend->invalidateTags($tags);
    }
  }

  /**
   * Gets all cache bin services.
   *
   * @return array
   *  An array of cache backend objects keyed by cache bins.
   */
  public static function getBins() {
    $bins = array();
    $container = \Drupal::getContainer();
    foreach ($container->getParameter('cache_bins') as $service_id => $bin) {
      $bins[$bin] = $container->get($service_id);
    }
    return $bins;
  }

  /**
   * Generates a hash from a query object, to be used as part of the cache key.
   *
   * This smart caching strategy saves Drupal from querying and rendering to
   * HTML when the underlying query is unchanged.
   *
   * Expensive queries should use the query builder to create the query and then
   * call this function. Executing the query and formatting results should
   * happen in a #pre_render callback.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A select query object.
   *
   * @return string
   *   A hash of the query arguments.
   */
  public static function keyFromQuery(SelectInterface $query) {
    $query->preExecute();
    $keys = array((string) $query, $query->getArguments());
    return hash('sha256', serialize($keys));
  }

}
