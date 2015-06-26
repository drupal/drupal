<?php

/**
 * @file
 * Contains \Drupal\Core\CacheDecorator\CacheDecoratorInterface.
 */

namespace Drupal\Core\CacheDecorator;

/**
 * Defines an interface for cache decorator implementations.
 */
interface CacheDecoratorInterface {

  /**
   * Specify the key to use when writing the cache.
   */
  public function setCacheKey($key);

  /**
   * Write the cache.
   */
  public function writeCache();

}
