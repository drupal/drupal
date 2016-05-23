<?php

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Core\Cache\MemoryBackend;

/**
 * Unit test of the memory cache backend using the generic cache unit test base.
 *
 * @group Cache
 */
class MemoryBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * Creates a new instance of MemoryBackend.
   *
   * @return
   *   A new MemoryBackend object.
   */
  protected function createCacheBackend($bin) {
    $backend = new MemoryBackend($bin);
    \Drupal::service('cache_tags.invalidator')->addInvalidator($backend);
    return $backend;
  }

}
