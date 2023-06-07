<?php

namespace Drupal\Core\Cache;

/**
 * Defines a value object to represent a cache redirect.
 *
 * @see \Drupal\Core\Cache\VariationCache::get()
 * @see \Drupal\Core\Cache\VariationCache::set()
 *
 * @ingroup cache
 * @internal
 */
class CacheRedirect implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * Constructs a CacheRedirect object.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability to redirect to.
   *
   * @see \Drupal\Core\Cache\VariationCache::createCacheIdFast()
   */
  public function __construct(CacheableDependencyInterface $cacheability) {
    // Cache redirects only care about cache contexts.
    $this->cacheContexts = $cacheability->getCacheContexts();
  }

}
