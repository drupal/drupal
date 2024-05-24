<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Defines a class which is capable of clearing the cache on plugin managers.
 */
class CachedDiscoveryClearer implements CachedDiscoveryClearerInterface {

  /**
   * The legacy stored discoveries.
   *
   * @var \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Pass
   *    the full set of services to the constructor instead.
   * @see https://www.drupal.org/node/3442229
   */
  protected $legacyCachedDiscoveries = [];

  /**
   * Adds a plugin manager to the active list.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $cached_discovery
   *   An object that implements the cached discovery interface, typically a
   *   plugin manager.
   *
   * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Pass
   *   the full set of services to the constructor instead.
   * @see https://www.drupal.org/node/3442229
   */
  public function addCachedDiscovery(CachedDiscoveryInterface $cached_discovery) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Pass the full set of services to the constructor instead. See https://www.drupal.org/node/3442229', E_USER_DEPRECATED);
    $this->legacyCachedDiscoveries[] = $cached_discovery;
  }

  /**
   * Constructs the CachedDiscoveryClearer service.
   *
   * @param \Traversable $cachedDiscoveries
   *   The cached discoveries.
   */
  public function __construct(
    #[AutowireIterator(tag: 'plugin_manager_cache_clear')]
    protected \Traversable $cachedDiscoveries,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    foreach ($this->cachedDiscoveries as $cached_discovery) {
      $cached_discovery->clearCachedDefinitions();
    }

    // @phpstan-ignore property.deprecated
    foreach ($this->legacyCachedDiscoveries as $cached_discovery) {
      $cached_discovery->clearCachedDefinitions();
    }
  }

}
