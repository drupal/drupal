<?php

namespace Drupal\Core\Cache;

/**
 * Allows to add cacheability metadata to an object for the current runtime.
 *
 * This must be used when changing an object in a way that affects its
 * cacheability. For example, when changing the active translation of an entity
 * based on the current content language then a cache context for that must be
 * added.
 */
interface RefinableCacheableDependencyInterface extends CacheableDependencyInterface {

  /**
   * Adds cache contexts.
   *
   * @param string[] $cache_contexts
   *   The cache contexts to be added.
   *
   * @return $this
   */
  public function addCacheContexts(array $cache_contexts);

  /**
   * Adds cache tags.
   *
   * @param string[] $cache_tags
   *   The cache tags to be added.
   *
   * @return $this
   */
  public function addCacheTags(array $cache_tags);

  /**
   * Merges the maximum age (in seconds) with the existing maximum age.
   *
   * The max age will be set to the given value if it is lower than the existing
   * value.
   *
   * @param int $max_age
   *   The max age to associate.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if a non-integer value is supplied.
   */
  public function mergeCacheMaxAge($max_age);

  /**
   * Adds a dependency on an object: merges its cacheability metadata.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|object $other_object
   *   The dependency. If the object implements CacheableDependencyInterface,
   *   then its cacheability metadata will be used. Otherwise, the passed in
   *   object must be assumed to be uncacheable, so max-age 0 is set.
   *
   * @return $this
   *
   * @see \Drupal\Core\Cache\CacheableMetadata::createFromObject()
   */
  public function addCacheableDependency($other_object);

}
