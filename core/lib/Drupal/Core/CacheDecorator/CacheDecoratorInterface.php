<?php

namespace Drupal\Core\CacheDecorator;

@trigger_error('The ' . __NAMESPACE__ . '\CacheDecoratorInterface is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3398182', E_USER_DEPRECATED);

/**
 * Defines an interface for cache decorator implementations.
 *
 * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0.
 *  There is no replacement.
 *
 * @see https://www.drupal.org/node/3398182
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
