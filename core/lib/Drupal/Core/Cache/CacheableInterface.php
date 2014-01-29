<?php
/**
 * @file
 * Contains \Drupal\Core\CacheableInterface
 */

namespace Drupal\Core\Cache;

/**
 * Defines an interface for objects which are potentially cacheable.
 */
interface CacheableInterface {

  /**
   * The cache keys associated with this potentially cacheable object.
   *
   * @return array
   *   An array of strings or cache constants, used to generate a cache ID.
   */
  public function getCacheKeys();

  /**
   * The cache tags associated with this potentially cacheable object.
   *
   * @return array
   *  An array of cache tags.
   */
  public function getCacheTags();

  /**
   * The bin to use for this potentially cacheable object.
   *
   * @return string
   *   The name of the cache bin to use.
   */
  public function getCacheBin();

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   */
  public function getCacheMaxAge();

  /**
   * Indicates whether this object is cacheable.
   *
   * @return bool
   *   Returns TRUE if the object is cacheable, FALSE otherwise.
   */
  public function isCacheable();

}
