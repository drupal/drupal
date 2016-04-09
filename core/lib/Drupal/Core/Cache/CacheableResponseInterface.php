<?php

namespace Drupal\Core\Cache;

/**
 * Defines an interface for responses that can expose cacheability metadata.
 *
 * @see \Drupal\Core\Cache\CacheableResponseTrait
 */
interface CacheableResponseInterface {

  /**
   * Adds a dependency on an object: merges its cacheability metadata.
   *
   * For instance, when a response depends on some configuration, an entity, or
   * an access result, we must make sure their cacheability metadata is present
   * on the response. This method makes doing that simple.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $dependency
   *   The dependency. If the object implements CacheableDependencyInterface,
   *   then its cacheability metadata will be used. Otherwise, the passed in
   *   object must be assumed to be uncacheable, so max-age 0 is set.
   *
   * @return $this
   *
   * @see \Drupal\Core\Cache\CacheableMetadata::createFromObject()
   */
  public function addCacheableDependency($dependency);

  /**
   * Returns the cacheability metadata for this response.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  public function getCacheableMetadata();

}
