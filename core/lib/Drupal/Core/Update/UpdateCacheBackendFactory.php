<?php

namespace Drupal\Core\Update;

use Drupal\Core\Cache\CacheFactoryInterface;

/**
 * Cache factory implementation for use during Drupal database updates.
 *
 * Decorates the regular runtime cache_factory service so that caches use
 * \Drupal\Core\Update\UpdateBackend.
 *
 * @see \Drupal\Core\Update\UpdateServiceProvider::register()
 */
class UpdateCacheBackendFactory implements CacheFactoryInterface {

  /**
   * The regular runtime cache_factory service.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * Instantiated update cache bins.
   *
   * @var \Drupal\Core\Update\UpdateBackend[]
   */
  protected $bins = [];

  /**
   * UpdateCacheBackendFactory constructor.
   *
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   The regular runtime cache_factory service.
   */
  public function __construct(CacheFactoryInterface $cache_factory) {
    $this->cacheFactory = $cache_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    if (!isset($this->bins[$bin])) {
      $this->bins[$bin] = new UpdateBackend($this->cacheFactory->get($bin), $bin);
    }
    return $this->bins[$bin];
  }

}
