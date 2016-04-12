<?php

namespace Drupal\Core\Cache;

/**
 * Trait to implement CacheableDependencyInterface for uncacheable objects.
 *
 * Use this for objects that are never cacheable.
 *
 * @see \Drupal\Core\Cache\CacheableDependencyInterface
 */
trait UncacheableDependencyTrait {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
