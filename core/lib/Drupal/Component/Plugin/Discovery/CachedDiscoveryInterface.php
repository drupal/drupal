<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface.
 */

namespace Drupal\Component\Plugin\Discovery;

/**
 * Interface for discovery compenents holding a cache of plugin definitions.
 */
interface CachedDiscoveryInterface extends DiscoveryInterface {

  /**
   * Clears static and persistent plugin definition caches.
   */
  public function clearCachedDefinitions();

}
