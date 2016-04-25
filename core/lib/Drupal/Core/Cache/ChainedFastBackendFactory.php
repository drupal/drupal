<?php

namespace Drupal\Core\Cache;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the chained fast cache backend factory.
 */
class ChainedFastBackendFactory implements CacheFactoryInterface {

  use ContainerAwareTrait;

  /**
   * The service name of the consistent backend factory.
   *
   * @var string
   */
  protected $consistentServiceName;

  /**
   * The service name of the fast backend factory.
   *
   * @var string
   */
  protected $fastServiceName;

  /**
   * Constructs ChainedFastBackendFactory object.
   *
   * @param \Drupal\Core\Site\Settings|NULL $settings
   *   (optional) The settings object.
   * @param string|NULL $consistent_service_name
   *   (optional) The service name of the consistent backend factory. Defaults
   *   to:
   *   - $settings->get('cache')['default'] (if specified)
   *   - 'cache.backend.database' (if the above isn't specified)
   * @param string|NULL $fast_service_name
   *   (optional) The service name of the fast backend factory. Defaults to:
   *   - 'cache.backend.apcu' (if the PHP process has APCu enabled)
   *   - NULL (if the PHP process doesn't have APCu enabled)
   */
  public function __construct(Settings $settings = NULL, $consistent_service_name = NULL, $fast_service_name = NULL) {
    // Default the consistent backend to the site's default backend.
    if (!isset($consistent_service_name)) {
      $cache_settings = isset($settings) ? $settings->get('cache') : array();
      $consistent_service_name = isset($cache_settings['default']) ? $cache_settings['default'] : 'cache.backend.database';
    }

    // Default the fast backend to APCu if it's available.
    if (!isset($fast_service_name) && function_exists('apcu_fetch')) {
      $fast_service_name = 'cache.backend.apcu';
    }

    $this->consistentServiceName = $consistent_service_name;

    // Do not use the fast chained backend during installation. In those cases,
    // we expect many cache invalidations and writes, the fast chained cache
    // backend performs badly in such a scenario.
    if (!drupal_installation_attempted()) {
      $this->fastServiceName = $fast_service_name;
    }
  }

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
    // Use the chained backend only if there is a fast backend available;
    // otherwise, just return the consistent backend directly.
    if (isset($this->fastServiceName)) {
      return new ChainedFastBackend(
        $this->container->get($this->consistentServiceName)->get($bin),
        $this->container->get($this->fastServiceName)->get($bin),
        $bin
      );
    }
    else {
      return $this->container->get($this->consistentServiceName)->get($bin);
    }
  }

}
