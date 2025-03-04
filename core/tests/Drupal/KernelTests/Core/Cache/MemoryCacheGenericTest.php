<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCache;

/**
 * Unit test of the memory cache using the generic cache unit test base.
 *
 * @group Cache
 */
class MemoryCacheGenericTest extends GenericCacheBackendUnitTestBase {

  /**
   * {@inheritdoc}
   */
  protected bool $testObjectProperties = FALSE;

  /**
   * Creates a new instance of MemoryCache.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   A new MemoryBackend object.
   */
  protected function createCacheBackend($bin) {
    $backend = new MemoryCache(\Drupal::service(TimeInterface::class));
    \Drupal::service('cache_tags.invalidator')->addInvalidator($backend);
    return $backend;
  }

}
