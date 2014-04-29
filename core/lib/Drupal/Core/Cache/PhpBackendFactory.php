<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\PhpBackendFactory.
 */

namespace Drupal\Core\Cache;

class PhpBackendFactory implements CacheFactoryInterface {

  /**
   * Gets PhpBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\PhpBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    return new PhpBackend($bin);
  }

}
