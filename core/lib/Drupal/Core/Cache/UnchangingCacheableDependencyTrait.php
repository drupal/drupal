<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\UnchangingCacheableDependencyTrait.
 */

namespace Drupal\Core\Cache;

/**
 * Trait to implement CacheableDependencyInterface for unchanging objects.
 *
 * @see \Drupal\Core\Cache\CacheableDependencyInterface
 */
trait UnchangingCacheableDependencyTrait {

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
    return Cache::PERMANENT;
  }

}
