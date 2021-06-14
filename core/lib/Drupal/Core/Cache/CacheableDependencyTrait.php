<?php

namespace Drupal\Core\Cache;

/**
 * Trait for \Drupal\Core\Cache\CacheableDependencyInterface.
 */
trait CacheableDependencyTrait {

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
   * Sets cacheability; useful for value object constructors.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability to set.
   *
   * @return $this
   */
  protected function setCacheability(CacheableDependencyInterface $cacheability) {
    $this->cacheContexts = $cacheability->getCacheContexts();
    $this->cacheTags = $cacheability->getCacheTags();
    $this->cacheMaxAge = $cacheability->getCacheMaxAge();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->cacheContexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->cacheMaxAge;
  }

}
