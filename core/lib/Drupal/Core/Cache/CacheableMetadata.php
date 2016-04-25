<?php

namespace Drupal\Core\Cache;

/**
 * Defines a generic class for passing cacheability metadata.
 *
 * @ingroup cache
 *
 */
class CacheableMetadata implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->cacheTags;
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
    $this->cacheTags = $cache_tags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->cacheContexts;
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
    $this->cacheContexts = $cache_contexts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->cacheMaxAge;
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

    $this->cacheMaxAge = $max_age;
    return $this;
  }

  /**
   * Merges the values of another CacheableMetadata object with this one.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $other
   *   The other CacheableMetadata object.
   *
   * @return static
   *   A new CacheableMetadata object, with the merged data.
   */
  public function merge(CacheableMetadata $other) {
    $result = clone $this;

    // This is called many times per request, so avoid merging unless absolutely
    // necessary.
    if (empty($this->cacheContexts)) {
      $result->cacheContexts = $other->cacheContexts;
    }
    elseif (empty($other->cacheContexts)) {
      $result->cacheContexts = $this->cacheContexts;
    }
    else {
      $result->cacheContexts = Cache::mergeContexts($this->cacheContexts, $other->cacheContexts);
    }

    if (empty($this->cacheTags)) {
      $result->cacheTags = $other->cacheTags;
    }
    elseif (empty($other->cacheTags)) {
      $result->cacheTags = $this->cacheTags;
    }
    else {
      $result->cacheTags = Cache::mergeTags($this->cacheTags, $other->cacheTags);
    }

    if ($this->cacheMaxAge === Cache::PERMANENT) {
      $result->cacheMaxAge = $other->cacheMaxAge;
    }
    elseif ($other->cacheMaxAge === Cache::PERMANENT) {
      $result->cacheMaxAge = $this->cacheMaxAge;
    }
    else {
      $result->cacheMaxAge = Cache::mergeMaxAges($this->cacheMaxAge, $other->cacheMaxAge);
    }
    return $result;
  }

  /**
   * Applies the values of this CacheableMetadata object to a render array.
   *
   * @param array &$build
   *   A render array.
   */
  public function applyTo(array &$build) {
    $build['#cache']['contexts'] = $this->cacheContexts;
    $build['#cache']['tags'] = $this->cacheTags;
    $build['#cache']['max-age'] = $this->cacheMaxAge;
  }

  /**
   * Creates a CacheableMetadata object with values taken from a render array.
   *
   * @param array $build
   *   A render array.
   *
   * @return static
   */
  public static function createFromRenderArray(array $build) {
    $meta = new static();
    $meta->cacheContexts = (isset($build['#cache']['contexts'])) ? $build['#cache']['contexts'] : [];
    $meta->cacheTags = (isset($build['#cache']['tags'])) ? $build['#cache']['tags'] : [];
    $meta->cacheMaxAge = (isset($build['#cache']['max-age'])) ? $build['#cache']['max-age'] : Cache::PERMANENT;
    return $meta;
  }

  /**
   * Creates a CacheableMetadata object from a depended object.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $object
   *   The object whose cacheability metadata to retrieve. If it implements
   *   CacheableDependencyInterface, its cacheability metadata will be used,
   *   otherwise, the passed in object must be assumed to be uncacheable, so
   *   max-age 0 is set.
   *
   * @return static
   */
  public static function createFromObject($object) {
    if ($object instanceof CacheableDependencyInterface) {
      $meta = new static();
      $meta->cacheContexts = $object->getCacheContexts();
      $meta->cacheTags = $object->getCacheTags();
      $meta->cacheMaxAge = $object->getCacheMaxAge();
      return $meta;
    }

    // Objects that don't implement CacheableDependencyInterface must be assumed
    // to be uncacheable, so set max-age 0.
    $meta = new static();
    $meta->cacheMaxAge = 0;
    return $meta;
  }

}
