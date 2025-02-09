<?php

namespace Drupal\Core\Cache;

/**
 * Registers cache tags for preloading.
 *
 * Implementations of \Drupal\Core\Cache\CacheTagsChecksumInterface that
 * support this interface will fetch registered cache tags on the next
 * lookup.
 *
 * @see \Drupal\Core\Cache\EventSubscriber\CacheTagPreloadSubscriber
 */
interface CacheTagsChecksumPreloadInterface {

  /**
   * Register cache tags for preloading.
   *
   * @param array $cache_tags
   *   List of cache tags to load.
   */
  public function registerCacheTagsForPreload(array $cache_tags): void;

}
