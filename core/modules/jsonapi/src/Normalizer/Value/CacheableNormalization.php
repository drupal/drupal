<?php

namespace Drupal\jsonapi\Normalizer\Value;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Use to store normalized data and its cacheability.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class CacheableNormalization implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * A normalized value.
   *
   * @var mixed
   */
  protected $normalization;

  /**
   * CacheableNormalization constructor.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   The cacheability metadata for the normalized data.
   * @param array|string|int|float|bool|null $normalization
   *   The normalized data. This value must not contain any
   *   CacheableNormalizations.
   */
  public function __construct(CacheableDependencyInterface $cacheability, $normalization) {
    assert((is_array($normalization) && static::hasNoNestedInstances($normalization)) || is_string($normalization) || is_int($normalization) || is_float($normalization) || is_bool($normalization) || is_null($normalization));
    $this->normalization = $normalization;
    $this->setCacheability($cacheability);
  }

  /**
   * Creates a CacheableNormalization instance without any special cacheability.
   *
   * @param array|string|int|float|bool|null $normalization
   *   The normalized data. This value must not contain any
   *   CacheableNormalizations.
   *
   * @return static
   *   The CacheableNormalization.
   */
  public static function permanent($normalization) {
    return new static(new CacheableMetadata(), $normalization);
  }

  /**
   * Gets the decorated normalization.
   *
   * @return array|string|int|float|bool|null
   *   The normalization.
   */
  public function getNormalization() {
    return $this->normalization;
  }

  /**
   * Converts the object to a CacheableOmission if the normalization is empty.
   *
   * @return self|\Drupal\jsonapi\Normalizer\Value\CacheableOmission
   *   A CacheableOmission if the normalization is considered empty, self
   *   otherwise.
   */
  public function omitIfEmpty() {
    return empty($this->normalization) ? new CacheableOmission($this) : $this;
  }

  /**
   * Gets a new CacheableNormalization with an additional dependency.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $dependency
   *   The new cacheable dependency.
   *
   * @return static
   *   A new object based on the current value with an additional cacheable
   *   dependency.
   */
  public function withCacheableDependency(CacheableDependencyInterface $dependency) {
    return new static(CacheableMetadata::createFromObject($this)->addCacheableDependency($dependency), $this->normalization);
  }

  /**
   * Collects an array of CacheableNormalizations into a single instance.
   *
   * @param \Drupal\jsonapi\Normalizer\Value\CacheableNormalization[] $cacheable_normalizations
   *   An array of CacheableNormalizations.
   *
   * @return static
   *   A new CacheableNormalization. Each input value's cacheability will be
   *   merged into the return value's cacheability. The return value's
   *   normalization will be an array of the input's normalizations. This method
   *   does *not* behave like array_merge() or NestedArray::mergeDeep().
   */
  public static function aggregate(array $cacheable_normalizations) {
    assert(Inspector::assertAllObjects($cacheable_normalizations, CacheableNormalization::class));
    return new static(
      array_reduce($cacheable_normalizations, function (CacheableMetadata $merged, CacheableNormalization $item) {
        return $merged->addCacheableDependency($item);
      }, new CacheableMetadata()),
      array_reduce(array_keys($cacheable_normalizations), function ($merged, $key) use ($cacheable_normalizations) {
        if (!$cacheable_normalizations[$key] instanceof CacheableOmission) {
          $merged[$key] = $cacheable_normalizations[$key]->getNormalization();
        }
        return $merged;
      }, [])
    );
  }

  /**
   * Ensures that no nested values are instances of this class.
   *
   * @param array|\Traversable $array
   *   The traversable object which may contain instance of this object.
   *
   * @return bool
   *   Whether the given object or its children have CacheableNormalizations in
   *   them.
   */
  protected static function hasNoNestedInstances($array) {
    foreach ($array as $value) {
      if ((is_array($value) || $value instanceof \Traversable) && !static::hasNoNestedInstances($value) || $value instanceof static) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
