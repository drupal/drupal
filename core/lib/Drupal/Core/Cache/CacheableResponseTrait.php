<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheableResponseTrait.
 */

namespace Drupal\Core\Cache;

/**
 * Provides an implementation of CacheableResponseInterface.
 *
 * @see \Drupal\Core\Cache\CacheableResponseInterface
 */
trait CacheableResponseTrait {

  /**
   * The cacheability metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheabilityMetadata;

  /**
   * {@inheritdoc}
   */
  public function addCacheableDependency($dependency) {
    // A trait doesn't have a constructor, so initialize the cacheability
    // metadata if that hasn't happened yet.
    if (!isset($this->cacheabilityMetadata)) {
      $this->cacheabilityMetadata = new CacheableMetadata();
    }

    $this->cacheabilityMetadata = $this->cacheabilityMetadata->merge(CacheableMetadata::createFromObject($dependency));

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // A trait doesn't have a constructor, so initialize the cacheability
    // metadata if that hasn't happened yet.
    if (!isset($this->cacheabilityMetadata)) {
      $this->cacheabilityMetadata = new CacheableMetadata();
    }

    return $this->cacheabilityMetadata;
  }

}
