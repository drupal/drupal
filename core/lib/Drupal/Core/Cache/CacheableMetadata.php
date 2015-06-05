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
    $result = new static();

    // This is called many times per request, so avoid merging unless absolutely
    // necessary.
    if (empty($this->contexts)) {
      $result->contexts = $other->contexts;
    }
    elseif (empty($other->contexts)) {
      $result->contexts = $this->contexts;
    }
    else {
      $result->contexts = Cache::mergeContexts($this->contexts, $other->contexts);
    }

    if (empty($this->tags)) {
      $result->tags = $other->tags;
    }
    elseif (empty($other->tags)) {
      $result->tags = $this->tags;
    }
    else {
      $result->tags = Cache::mergeTags($this->tags, $other->tags);
    }

    if ($this->maxAge === Cache::PERMANENT) {
      $result->maxAge = $other->maxAge;
    }
    elseif ($other->maxAge === Cache::PERMANENT) {
      $result->maxAge = $this->maxAge;
    }
    else {
      $result->maxAge = Cache::mergeMaxAges($this->maxAge, $other->maxAge);
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
    $build['#cache']['contexts'] = $this->contexts;
    $build['#cache']['tags'] = $this->tags;
    $build['#cache']['max-age'] = $this->maxAge;
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
    $meta->contexts = (isset($build['#cache']['contexts'])) ? $build['#cache']['contexts'] : [];
    $meta->tags = (isset($build['#cache']['tags'])) ? $build['#cache']['tags'] : [];
    $meta->maxAge = (isset($build['#cache']['max-age'])) ? $build['#cache']['max-age'] : Cache::PERMANENT;
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
      $meta->contexts = $object->getCacheContexts();
      $meta->tags = $object->getCacheTags();
      $meta->maxAge = $object->getCacheMaxAge();
      return $meta;
    }

    // Objects that don't implement CacheableDependencyInterface must be assumed
    // to be uncacheable, so set max-age 0.
    $meta = new static();
    $meta->maxAge = 0;
    return $meta;
  }

}
