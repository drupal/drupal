<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscoveryCollector.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A CacheCollector implementation for building library extension info.
 */
class LibraryDiscoveryCollector extends CacheCollector {

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey = 'library_info';

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The library discovery parser.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryParser
   */
  protected $discoveryParser;

  /**
   * Constructs a CacheCollector object.
   *
   * @param string $cid
   *   The cid for the array being cached.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Asset\LibraryDiscoveryParser $discovery_parser
   *   The library discovery parser.
   */
  public function __construct(CacheBackendInterface $cache, LockBackendInterface $lock, LibraryDiscoveryParser $discovery_parser) {
    parent::__construct($this->cacheKey, $cache, $lock, array($this->cacheKey));

    $this->discoveryParser = $discovery_parser;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $this->storage[$key] = $this->discoveryParser->buildByExtension($key);
    $this->persist($key);

    return $this->storage[$key];
  }

}
