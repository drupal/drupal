<?php
/**
 * @file
 * Contains \Drupal\Core\CacheableInterface
 */

namespace Drupal\Core\Cache;

/**
 * Defines an interface for objects which are potentially cacheable.
 *
 * All cacheability metadata exposed in this interface is bubbled to parent
 * objects when they are cached: if a child object needs to be varied by certain
 * cache contexts, invalidated by certain cache tags, expire after a certain
 * maximum age, then so should any parent object. And if a child object is not
 * cacheable, then neither is any parent object.
 * The only cacheability metadata that must not be bubbled, are the cache keys:
 * they're explicitly intended to be used to generate the cache item ID when
 * caching the object they're on.
 *
 * @ingroup cache
 */
interface CacheableInterface extends CacheableDependencyInterface {

  /**
   * The cache keys associated with this potentially cacheable object.
   *
   * These identify the object.
   *
   * @return string[]
   *   An array of strings, used to generate a cache ID.
   */
  public function getCacheKeys();

  /**
   * Indicates whether this object is cacheable.
   *
   * @return bool
   *   Returns TRUE if the object is cacheable, FALSE otherwise.
   */
  public function isCacheable();

}
