<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContextInterface.
 */

namespace Drupal\Core\Cache;

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
   */
  public function getContext($parameter = NULL);

}
