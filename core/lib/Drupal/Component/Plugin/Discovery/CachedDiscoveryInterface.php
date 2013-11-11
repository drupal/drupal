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
   *
   * Don't resort to calling \Drupal::cache()->delete() and friends to make
   * Drupal detect new or updated plugin definitions. Always use this method on
   * the appropriate plugin type's plugin manager!
   */
  public function clearCachedDefinitions();

}
