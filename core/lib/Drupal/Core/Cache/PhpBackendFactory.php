<?php

namespace Drupal\Core\Cache;

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
   */
  public function __construct(CacheTagsChecksumInterface $checksum_provider) {
    $this->checksumProvider = $checksum_provider;
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
  function get($bin) {
    return new PhpBackend($bin, $this->checksumProvider);
  }

}
