<?php

namespace Drupal\Core\Cache;

/**
 * Defines a stub cache backend factory.
 */
class NullBackendFactory implements CacheFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new NullBackend($bin);
  }

}
