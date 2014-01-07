<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheFactory.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the cache backend factory.
 */
use Drupal\Component\Utility\Settings;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheFactory extends ContainerAware implements CacheFactoryInterface {

  /**
   * The settings array.
   *
   * @var \Drupal\Component\Utility\Settings
   */
  protected $settings;

  /**
   * Constructs CacheFactory object.
   *
   * @param \Drupal\Component\Utility\Settings $settings
   *   The settings array.
   */
  function __construct(Settings $settings) {
    $this->settings = $settings;
  }

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
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend object associated with the specified bin.
   */
  public function get($bin) {
    $cache_settings = $this->settings->get('cache');
    if (isset($cache_settings[$bin])) {
      $service_name = $cache_settings[$bin];
    }
    elseif (isset($cache_settings['default'])) {
      $service_name = $cache_settings['default'];
    }
    else {
      $service_name = 'cache.backend.database';
    }
    return $this->container->get($service_name)->get($bin);
  }

}
