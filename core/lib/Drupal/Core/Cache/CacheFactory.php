<?php

/**
 * @file
 * Contains Drupal\Core\Cache\CacheFactory.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the cache backend factory.
 */
class CacheFactory {

  /**
   * Instantiates a cache backend class for a given cache bin.
   *
   * By default, this returns an instance of the
   * Drupal\Core\Cache\DatabaseBackend class.
   *
   * Classes implementing Drupal\Core\Cache\CacheBackendInterface can register
   * themselves both as a default implementation and for specific bins.
   *
   * @param string $bin
   *   The cache bin for which a cache backend object should be returned.
   *
   * @return Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public static function get($bin) {
    // Check whether there is a custom class defined for the requested bin or
    // use the default 'cache' definition otherwise.
    $cache_backends = self::getBackends();
    $class = isset($cache_backends[$bin]) ? $cache_backends[$bin] : $cache_backends['cache'];
    return new $class($bin);
  }

  /**
   * Returns a list of cache backends for this site.
   *
   * @return array
   *   An associative array with cache bins as keys, and backend class names as
   *   value.
   */
  public static function getBackends() {
    // @todo Improve how cache backend classes are defined. Cannot be
    //   configuration, since e.g. the CachedStorage config storage controller
    //   requires the definition in its constructor already.
    global $conf;
    $cache_backends = isset($conf['cache_classes']) ? $conf['cache_classes'] : array();
    // Ensure there is a default 'cache' bin definition.
    $cache_backends += array('cache' => 'Drupal\Core\Cache\DatabaseBackend');
    return $cache_backends;
  }

}
