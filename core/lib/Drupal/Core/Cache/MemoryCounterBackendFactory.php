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
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(protected ?TimeInterface $time = NULL) {
    if (!$time) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3387233', E_USER_DEPRECATED);
      $this->time = \Drupal::service(TimeInterface::class);
    }
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
