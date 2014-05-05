<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\NullBackendFactory.
 */

namespace Drupal\Core\Cache;

class NullBackendFactory implements CacheFactoryInterface {

  /**
   * {@inheritdoc}
   */
  function get($bin) {
    return new NullBackend($bin);
  }

}
