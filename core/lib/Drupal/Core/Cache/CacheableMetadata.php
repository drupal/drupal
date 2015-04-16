<?php
/**
 * @file
 * Contains \Drupal\Core\CacheableMetadata
 */

namespace Drupal\Core\Cache;

/**
 * Defines a generic class for passing cacheability metadata.
 *
 * @ingroup cache
 */
class CacheableMetadata implements CacheableDependencyInterface {

  /**
   * Cache contexts.
   *
   * @var string[]
   */
  protected $contexts = [];

  /**
   * Cache tags.
   *
   * @var string[]
   */
  protected $tags = [];

  /**
   * Cache max-age.
   *
   * @var int
   */
  protected $maxAge = Cache::PERMANENT;

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->tags;
  }

  /**
   * Adds cache tags.
   *
   * @param string[] $cache_tags
   *   The cache tags to be added.
   *
   * @return $this
   */
  public function addCacheTags(array $cache_tags) {
    $this->tags = Cache::mergeTags($this->tags, $cache_tags);
    return $this;
  }

  /**
   * Sets cache tags.
   *
   * @param string[] $cache_tags
   *   The cache tags to be associated.
   *
   * @return $this
   */
  public function setCacheTags(array $cache_tags) {
    $this->tags = $cache_tags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->contexts;
  }

  /**
   * Adds cache contexts.
   *
   * @param string[] $cache_contexts
   *   The cache contexts to be added.
   *
   * @return $this
   */
  public function addCacheContexts(array $cache_contexts) {
    $this->contexts = Cache::mergeContexts($this->contexts, $cache_contexts);
    return $this;
  }

  /**
   * Sets cache contexts.
   *
   * @param string[] $cache_contexts
   *   The cache contexts to be associated.
   *
   * @return $this
   */
  public function setCacheContexts(array $cache_contexts) {
    $this->contexts = $cache_contexts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->maxAge;
  }

  /**
   * Sets the maximum age (in seconds).
   *
   * Defaults to Cache::PERMANENT
   *
   * @param int $max_age
   *   The max age to associate.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If a non-integer value is supplied.
   */
  public function setCacheMaxAge($max_age) {
    if (!is_int($max_age)) {
      throw new \InvalidArgumentException('$max_age must be an integer');
    }

    $this->maxAge = $max_age;
    return $this;
  }

}
