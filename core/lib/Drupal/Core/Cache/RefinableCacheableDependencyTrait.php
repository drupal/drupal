<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\RefinableCacheableDependencyTrait.
 */

namespace Drupal\Core\Cache;

/**
 * Trait for \Drupal\Core\Cache\RefinableCacheableDependencyInterface.
 */
trait RefinableCacheableDependencyTrait {

  /**
   * Cache contexts.
   *
   * @var string[]
   */
  protected $cacheContexts = [];

  /**
   * Cache tags.
   *
   * @var string[]
   */
  protected $cacheTags = [];

  /**
   * Cache max-age.
   *
   * @var int
   */
  protected $cacheMaxAge = Cache::PERMANENT;

  /**
   * {@inheritdoc}
   */
  public function addCacheContexts(array $cache_contexts) {
    $this->cacheContexts = Cache::mergeContexts($this->cacheContexts, $cache_contexts);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCacheTags(array $cache_tags) {
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $cache_tags);
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
