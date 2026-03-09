<?php

namespace Drupal\Core\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Defines a class which is capable of clearing the cache on plugin managers.
 */
class CachedDiscoveryClearer implements CachedDiscoveryClearerInterface {

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
  }

}
