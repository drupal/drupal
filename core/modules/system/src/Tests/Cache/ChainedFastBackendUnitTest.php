<?php

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\PhpBackend;

/**
 * Unit test of the fast chained backend using the generic cache unit test base.
 *
 * @group Cache
 */
class ChainedFastBackendUnitTest extends GenericCacheBackendUnitTestBase {

  /**
   * Creates a new instance of ChainedFastBackend.
   *
   * @return \Drupal\Core\Cache\ChainedFastBackend
   *   A new ChainedFastBackend object.
   */
  protected function createCacheBackend($bin) {
    $consistent_backend = new DatabaseBackend(\Drupal::service('database'), \Drupal::service('cache_tags.invalidator.checksum'), $bin);
    $fast_backend = new PhpBackend($bin, \Drupal::service('cache_tags.invalidator.checksum'));
    $backend = new ChainedFastBackend($consistent_backend, $fast_backend, $bin);
    // Explicitly register the cache bin as it can not work through the
    // cache bin list in the container.
    \Drupal::service('cache_tags.invalidator')->addInvalidator($backend);
    return $backend;
  }

}
