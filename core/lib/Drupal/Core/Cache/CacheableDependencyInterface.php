<?php

namespace Drupal\Core\Cache;

/**
 * Defines an interface for objects which may be used by other cached objects.
 *
 * All cacheability metadata exposed in this interface is bubbled to parent
 * objects when they are cached: if a child object needs to be varied by certain
 * cache contexts, invalidated by certain cache tags, expire after a certain
 * maximum age, then so should any parent object.
 *
 * @ingroup cache
 */
interface CacheableDependencyInterface {

  /**
   * The cache contexts associated with this object.
   *
   * These identify a specific variation/representation of the object.
   *
   * Cache contexts are tokens: placeholders that are converted to cache keys by
   * the @cache_contexts_manager service. The replacement value depends on the
   * request context (the current URL, language, and so on). They're converted
   * before storing an object in cache.
   *
   * @return string[]
   *   An array of cache context tokens, used to generate a cache ID.
   *
   * @see \Drupal\Core\Cache\Context\CacheContextsManager::convertTokensToKeys()
   */
  public function getCacheContexts();

  /**
   * The cache tags associated with this object.
   *
   * When this object is modified, these cache tags will be invalidated.
   *
   * @return string[]
   *  A set of cache tags.
   */
  public function getCacheTags();

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   */
  public function getCacheMaxAge();

}
