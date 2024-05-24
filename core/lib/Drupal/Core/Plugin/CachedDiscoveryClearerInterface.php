<?php

namespace Drupal\Core\Plugin;

/**
 * Provides a way to clear static caches of all plugin managers.
 */
interface CachedDiscoveryClearerInterface {

  /**
   * Clears the cache on all cached discoveries.
   */
  public function clearCachedDefinitions();

}
