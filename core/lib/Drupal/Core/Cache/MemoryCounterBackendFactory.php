<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;

class MemoryCounterBackendFactory implements CacheFactoryInterface {

  /**
   * Instantiated memory cache bins.
   *
   * @var \Drupal\Core\Cache\MemoryBackend[]
   */
  protected $bins = [];

  /**
   * Constructs a MemoryCounterBackendFactory object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(protected TimeInterface $time) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    if (!isset($this->bins[$bin])) {
      $this->bins[$bin] = new MemoryCounterBackend($this->time);
    }
    return $this->bins[$bin];
  }

}
