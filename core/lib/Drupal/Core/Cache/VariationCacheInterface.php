<?php

namespace Drupal\Core\Cache;

/**
 * Defines an interface for variation cache implementations.
 *
 * A variation cache wraps any provided cache backend and adds support for cache
 * contexts to it. The actual caching still happens in the original cache
 * backend.
 *
 * @ingroup cache
 */
interface VariationCacheInterface {

  /**
   * Gets a cache entry based on cache keys.
   *
   * @param string[] $keys
   *   The cache keys to retrieve the cache entry for.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The cache metadata of the data to store before other systems had a chance
   *   to adjust it. This is also commonly known as "pre-bubbling" cacheability.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  public function get(array $keys, CacheableDependencyInterface $initial_cacheability);

  /**
   * Stores data in the cache.
   *
   * @param string[] $keys
   *   The cache keys of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cache metadata of the data to store.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The cache metadata of the data to store before other systems had a chance
   *   to adjust it. This is also commonly known as "pre-bubbling" cacheability.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   *
   * @throws \LogicException
   *   Thrown when cacheability is provided that does not contain a cache
   *   context or does not completely contain the initial cacheability.
   */
  public function set(array $keys, $data, CacheableDependencyInterface $cacheability, CacheableDependencyInterface $initial_cacheability): void;

  /**
   * Deletes an item from the cache.
   *
   * To stay consistent with ::get(), this only affects the active variation,
   * not all possible variations for the associated cache contexts.
   *
   * @param string[] $keys
   *   The cache keys of the data to delete.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The cache metadata of the data to store before other systems had a chance
   *   to adjust it. This is also commonly known as "pre-bubbling" cacheability.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::delete()
   */
  public function delete(array $keys, CacheableDependencyInterface $initial_cacheability): void;

  /**
   * Marks a cache item as invalid.
   *
   * To stay consistent with ::get(), this only affects the active variation,
   * not all possible variations for the associated cache contexts.
   *
   * @param string[] $keys
   *   The cache keys of the data to invalidate.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $initial_cacheability
   *   The cache metadata of the data to store before other systems had a chance
   *   to adjust it. This is also commonly known as "pre-bubbling" cacheability.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::invalidate()
   */
  public function invalidate(array $keys, CacheableDependencyInterface $initial_cacheability): void;

}
