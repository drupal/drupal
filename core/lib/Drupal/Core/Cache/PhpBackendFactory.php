<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;

class PhpBackendFactory implements CacheFactoryInterface {

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a PhpBackendFactory object.
   *
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(CacheTagsChecksumInterface $checksum_provider, protected ?TimeInterface $time = NULL) {
    $this->checksumProvider = $checksum_provider;
    if (!$time) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3387233', E_USER_DEPRECATED);
      $this->time = \Drupal::service(TimeInterface::class);
    }
  }

  /**
   * Gets PhpBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\PhpBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    return new PhpBackend($bin, $this->checksumProvider, $this->time);
  }

}
