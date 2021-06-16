<?php

namespace Drupal\Core\Cache;

/**
 * Trait for \Drupal\Core\Cache\RefinableCacheableDependencyInterface.
 */
trait RefinableCacheableDependencyTrait {

  use CacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($other_object) {
    if ($other_object instanceof CacheableDependencyInterface) {
      $this->addCacheContexts($other_object->getCacheContexts());
      $this->addCacheTags($other_object->getCacheTags());
      $this->mergeCacheMaxAge($other_object->getCacheMaxAge());
    }
    else {
      // Not a cacheable dependency, this can not be cached.
      $this->cacheMaxAge = 0;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    if ($cache_contexts) {
      $this->cacheContexts = Cache::mergeContexts($this->cacheContexts, $cache_contexts);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    if ($cache_tags) {
      $this->cacheTags = Cache::mergeTags($this->cacheTags, $cache_tags);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeCacheMaxAge($max_age) {
    $this->cacheMaxAge = Cache::mergeMaxAges($this->cacheMaxAge, $max_age);
    return $this;
  }

}
