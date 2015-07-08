<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\CalculatedCacheContextInterface.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Provides an interface for defining a calculated cache context service.
 */
interface CalculatedCacheContextInterface {

  /**
   * Returns the label of the cache context.
   *
   * @return string
   *   The label of the cache context.
   *
   * @see Cache
   */
  public static function getLabel();

  /**
   * Returns the string representation of the cache context.
   *
   * A cache context service's name is used as a token (placeholder) cache key,
   * and is then replaced with the string returned by this method.
   *
   * @param string|null $parameter
   *   The parameter, or NULL to indicate all possible parameter values.
   *
   * @return string
   *   The string representation of the cache context. When $parameter is NULL,
   *   a value representing all possible parameters must be generated.
   *
   * @throws \LogicException
   *   Thrown if the passed in parameter is invalid.
   */
  public function getContext($parameter = NULL);

  /**
   * Gets the cacheability metadata for the context based on the parameter value.
   *
   * There are three valid cases for the returned CacheableMetadata object:
   * - An empty object means this can be optimized away safely.
   * - A max-age of 0 means that this context can never be optimized away. It
   *   will never bubble up and cache tags will not be used.
   * - Any non-zero max-age and cache tags will bubble up into the cache item
   *   if this is optimized away to allow for invalidation if the context
   *   value changes.
   *
   * @param string|null $parameter
   *   The parameter, or NULL to indicate all possible parameter values.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A cacheable metadata object.
   *
   * @throws \LogicException
   *   Thrown if the passed in parameter is invalid.
   */
  public function getCacheableMetadata($parameter = NULL);

}
