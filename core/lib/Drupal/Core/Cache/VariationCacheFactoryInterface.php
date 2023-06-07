<?php

namespace Drupal\Core\Cache;

/**
 * An interface defining variation cache factory classes.
 */
interface VariationCacheFactoryInterface {

  /**
   * Gets a variation cache backend for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a variation cache backend should be returned.
   *
   * @return \Drupal\Core\Cache\VariationCacheInterface
   *   The variation cache backend associated with the specified bin.
   */
  public function get($bin);

}
