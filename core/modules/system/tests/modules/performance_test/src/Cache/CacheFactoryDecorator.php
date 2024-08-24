<?php

declare(strict_types=1);

namespace Drupal\performance_test\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\performance_test\PerformanceDataCollector;

/**
 * Decorates a cache factory to register all calls to the cache system.
 */
class CacheFactoryDecorator implements CacheFactoryInterface {

  /**
   * All wrapped cache backends.
   *
   * @var \Drupal\performance_data\Cache\CacheBackendDecorator[]
   */
  protected array $cacheBackends = [];

  public function __construct(protected readonly CacheFactoryInterface $cacheFactory, protected readonly PerformanceDataCollector $performanceDataCollector) {}

  /**
   * {@inheritdoc}
   */
  public function get($bin): CacheBackendInterface {
    if (!isset($this->cacheBackends[$bin])) {
      $cache_backend = $this->cacheFactory->get($bin);
      // Don't log memory cache operations.
      if (!$cache_backend instanceof MemoryCacheInterface && !$cache_backend instanceof MemoryBackend) {
        $this->cacheBackends[$bin] = new CacheBackendDecorator($this->performanceDataCollector, $cache_backend, $bin);
      }
      else {
        $this->cacheBackends[$bin] = $cache_backend;
      }
    }

    return $this->cacheBackends[$bin];
  }

}
