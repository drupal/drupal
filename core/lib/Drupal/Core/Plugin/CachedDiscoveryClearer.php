<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\CachedDiscoveryClearer.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a class which is capable of clearing the cache on plugin managers.
 */
class CachedDiscoveryClearer {

  /**
   * The stored discoveries.
   *
   * @var \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface[]
   */
  protected $cachedDiscoveries = array();

  /**
   * Adds a plugin manager to the active list.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $cached_discovery
   *   An object that implements the cached discovery interface, typically a
   *   plugin manager.
   */
  public function addCachedDiscovery(CachedDiscoveryInterface $cached_discovery) {
    $this->cachedDiscoveries[] = $cached_discovery;
  }

  /**
   * Clears the cache on all cached discoveries.
   */
  public function clearCachedDefinitions() {
    foreach ($this->cachedDiscoveries as $cached_discovery) {
      $cached_discovery->clearCachedDefinitions();
    }
  }

}
