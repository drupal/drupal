<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\ApcuBackendFactory.
 */

namespace Drupal\Core\Cache;

use \Drupal\Component\Utility\Crypt;

class ApcuBackendFactory implements CacheFactoryInterface {

  /**
   * The site prefix string.
   *
   * @var string
   */
  protected $sitePrefix;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs an ApcuBackendFactory object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   */
  public function __construct($root, CacheTagsChecksumInterface $checksum_provider) {
    $this->sitePrefix = Crypt::hashBase64($root . '/' . conf_path());
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * Gets ApcuBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\ApcuBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    return new ApcuBackend($bin, $this->sitePrefix, $this->checksumProvider);
  }

}
