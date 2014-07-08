<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\ChainedFastBackendFactory.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the chained fast cache backend factory.
 */
class ChainedFastBackendFactory extends CacheFactory {

  /**
   * Instantiates a chained, fast cache backend class for a given cache bin.
   *
   * @param string $bin
   *   The cache bin for which a cache backend object should be returned.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public function get($bin) {
    $consistent_service = 'cache.backend.database';
    $fast_service = 'cache.backend.apcu';

    $cache_settings = $this->settings->get('cache');
    if (isset($cache_settings['chained_fast_cache']) && is_array($cache_settings['chained_fast_cache'])) {
      if (!empty($cache_settings['chained_fast_cache']['consistent'])) {
        $consistent_service = $cache_settings['chained_fast_cache']['consistent'];
      }
      if (!empty($cache_settings['chained_fast_cache']['fast'])) {
        $fast_service = $cache_settings['chained_fast_cache']['fast'];
      }
    }

    return new ChainedFastBackend(
      $this->container->get($consistent_service)->get($bin),
      $this->container->get($fast_service)->get($bin),
      $bin
    );
  }

}
