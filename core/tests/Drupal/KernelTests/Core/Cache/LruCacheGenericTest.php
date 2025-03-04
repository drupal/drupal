<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\LruMemoryCache;

/**
 * Unit test of the LRU memory cache using the generic cache unit test base.
 *
 * @group Cache
 */
class LruCacheGenericTest extends GenericCacheBackendUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected bool $testObjectProperties = FALSE;

  /**
   * Creates a new instance of LruMemoryCache.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   A new MemoryBackend object.
   */
  protected function createCacheBackend($bin) {
    $backend = new LruMemoryCache(\Drupal::service(TimeInterface::class), 300);
    \Drupal::service('cache_tags.invalidator')->addInvalidator($backend);
    return $backend;
  }

}
