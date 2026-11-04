<?php

namespace Drupal\Core\Cache;

use Drupal\Core\Site\Settings;
use Psr\Container\ContainerInterface;

/**
 * Defines the cache backend factory.
 */
class CacheFactory implements CacheFactoryInterface {

  /**
   * The service container.
   */
  protected ContainerInterface $container;

  /**
   * Sets the service container.
   */
  public function setContainer(ContainerInterface $container): void {
    $this->container = $container;
  }

  /**
   * Constructs CacheFactory object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   * @param array $defaultBinBackends
   *   (optional) A mapping of bin to backend service name. Mappings in
   *   $settings take precedence over this.
   * @param array $memoryDefaultBinBackends
   *   (optional) A mapping of bin to backend service name. Mappings in
   *   $settings take precedence over this.
   */
  public function __construct(
    protected Settings $settings,
    protected array $defaultBinBackends = [],
    protected array $memoryDefaultBinBackends = [],
  ) {
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
    // First, look for a cache bin specific setting.
    if (isset($cache_settings['bins'][$bin])) {
      $service_name = $cache_settings['bins'][$bin];
    }
    // Second, use the default backend specified by the cache bin.
    elseif (isset($this->defaultBinBackends[$bin])) {
      $service_name = $this->defaultBinBackends[$bin];
    }
    elseif (isset($this->memoryDefaultBinBackends[$bin])) {
      $service_name = $this->memoryDefaultBinBackends[$bin];
    }
    // Third, use configured default backend.
    elseif (isset($cache_settings['default'])) {
      $service_name = $cache_settings['default'];
    }
    else {
      // Fall back to the database backend if nothing else is configured.
      $service_name = 'cache.backend.database';
    }
    return $this->container->get($service_name)->get($bin);
  }

}
