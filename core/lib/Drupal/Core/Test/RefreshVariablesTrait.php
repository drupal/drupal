<?php

namespace Drupal\Core\Test;

use Drupal\Core\Cache\Cache;

/**
 * Provides a method to refresh in-memory configuration and state information.
 */
trait RefreshVariablesTrait {

  /**
   * Refreshes in-memory configuration and state information.
   *
   * Useful after a page request is made that changes configuration or state in
   * a different thread.
   *
   * In other words calling a settings page with $this->submitForm() with a
   * changed value would update configuration to reflect that change, but in the
   * thread that made the call (thread running the test) the changed values
   * would not be picked up.
   *
   * This method clears the cache and loads a fresh copy.
   */
  protected function refreshVariables() {
    // Clear the tag cache.
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
    foreach (Cache::getBins() as $backend) {
      if (is_callable([$backend, 'reset'])) {
        $backend->reset();
      }
    }
    foreach (Cache::getMemoryBins() as $backend) {
      if (is_callable([$backend, 'reset'])) {
        $backend->reset();
      }
    }

    \Drupal::service('config.factory')->reset();
    \Drupal::service('state')->reset();
  }

}
