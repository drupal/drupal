<?php

namespace Drupal\Core\Cache;

/**
 * An interface defining cache factory classes.
 */
interface CacheFactoryInterface {

  /**
   * Gets a cache backend class for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a cache backend object should be returned.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public function get($bin);

}
