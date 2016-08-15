<?php

namespace Drupal\Core\Cache;

class MemoryBackendFactory implements CacheFactoryInterface {

  /**
   * Instantiated memory cache bins.
   *
   * @var \Drupal\Core\Cache\MemoryBackend[]
   */
  protected $bins = array();

  /**
   * {@inheritdoc}
   */
  function get($bin) {
    if (!isset($this->bins[$bin])) {
      $this->bins[$bin] = new MemoryBackend();
    }
    return $this->bins[$bin];
  }

}
