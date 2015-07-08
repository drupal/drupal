<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\Context\CacheContextInterface.
 */

namespace Drupal\Core\Cache\Context;

/**
 * Provides an interface for defining a cache context service.
 */
interface CacheContextInterface {

  /**
   * Returns the label of the cache context.
   *
   * @return string
   *   The label of the cache context.
   */
  public static function getLabel();

  /**
   * Returns the string representation of the cache context.
   *
   * A cache context service's name is used as a token (placeholder) cache key,
   * and is then replaced with the string returned by this method.
   *
   * @return string
   *   The string representation of the cache context.
   */
  public function getContext();

  /**
   * Gets the cacheability metadata for the context.
   *
   * There are three valid cases for the returned CacheableMetadata object:
   * - An empty object means this can be optimized away safely.
   * - A max-age of 0 means that this context can never be optimized away. It
   *   will never bubble up and cache tags will not be used.
   * - Any non-zero max-age and cache tags will bubble up into the cache item
   *   if this is optimized away to allow for invalidation if the context
   *   value changes.
   *
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A cacheable metadata object.
   */
  public function getCacheableMetadata();

}
