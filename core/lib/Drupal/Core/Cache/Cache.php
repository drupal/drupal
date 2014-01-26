<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Cache.
 */

namespace Drupal\Core\Cache;

/**
 * Helper methods for cache.
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

}
