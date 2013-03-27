<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\MemoryBackendFactory.
 */

namespace Drupal\Core\Cache;

class MemoryBackendFactory {

  /**
   * Gets MemoryBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\MemoryBackend
   *   The cache backend object for the specified cache bin.
   */
  function get($bin) {
    return new MemoryBackend($bin);
  }

}
