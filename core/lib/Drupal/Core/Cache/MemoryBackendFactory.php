<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\MemoryBackendFactory.
 */

namespace Drupal\Core\Cache;

class MemoryBackendFactory implements CacheFactoryInterface {

  /**
   * {@inheritdoc}
   */
  function get($bin) {
    return new MemoryBackend($bin);
  }

}
