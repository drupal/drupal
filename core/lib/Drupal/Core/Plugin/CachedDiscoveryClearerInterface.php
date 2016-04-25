<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;

/**
 * Provides a way to clear static caches of all plugin managers.
 */
interface CachedDiscoveryClearerInterface {

  /**
   * Adds a plugin manager to the active list.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $cached_discovery
   *   An object that implements the cached discovery interface, typically a
   *   plugin manager.
   */
  public function addCachedDiscovery(CachedDiscoveryInterface $cached_discovery);

  /**
   * Clears the cache on all cached discoveries.
   */
  public function clearCachedDefinitions();

}
