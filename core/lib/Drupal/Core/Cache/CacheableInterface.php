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
interface CacheableInterface {

  /**
   * The cache keys associated with this potentially cacheable object.
   *
   * @return string[]
   *   An array of strings, used to generate a cache ID.
   */
  public function getCacheKeys();

  /**
   * The cache contexts associated with this potentially cacheable object.
   *
   * Cache contexts are tokens: placeholders that are converted to cache keys by
   * the @cache_contexts service. The replacement value depends on the request
   * context (the current URL, language, and so on). They're converted before
   * storing an object in cache.
   *
   * @return string[]
   *   An array of cache context tokens, used to generate a cache ID.
   *
   * @see \Drupal\Core\Cache\CacheContexts::convertTokensToKeys()
   */
  public function getCacheContexts();

  /**
   * The cache tags associated with this potentially cacheable object.
   *
   * @return string[]
   *  An array of cache tags.
   */
  public function getCacheTags();

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
