<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;

/**
 * Defines a class which is capable of clearing the cache on plugin managers.
 */
class CachedDiscoveryClearer implements CachedDiscoveryClearerInterface {

  /**
   * The stored discoveries.
   *
   * @var \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   */
  protected $cachedDiscoveries = [];

  /**
   * {@inheritdoc}
   */
  public function addCachedDiscovery(CachedDiscoveryInterface $cached_discovery) {
    $this->cachedDiscoveries[] = $cached_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    foreach ($this->cachedDiscoveries as $cached_discovery) {
      $cached_discovery->clearCachedDefinitions();
    }
  }

}
