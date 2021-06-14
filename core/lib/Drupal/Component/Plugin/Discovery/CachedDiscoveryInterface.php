<?php

namespace Drupal\Component\Plugin\Discovery;

/**
 * Interface for discovery components holding a cache of plugin definitions.
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

  /**
   * Disable the use of caches.
   *
   * Can be used to ensure that uncached plugin definitions are returned,
   * without invalidating all cached information.
   *
   * This will also remove all local/static caches.
   *
   * @param bool $use_caches
   *   FALSE to not use any caches.
   */
  public function useCaches($use_caches = FALSE);

}
