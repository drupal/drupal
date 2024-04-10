<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\PhpBackend;

/**
 * Unit test of the fast chained backend using the generic cache unit test base.
 *
 * @group Cache
 */
class ChainedFastBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * Creates a new instance of ChainedFastBackend.
   *
   * @return \Drupal\Core\Cache\ChainedFastBackend
   *   A new ChainedFastBackend object.
   */
  protected function createCacheBackend($bin) {
    $consistent_backend = new DatabaseBackend(\Drupal::service('database'), \Drupal::service('cache_tags.invalidator.checksum'), $bin, \Drupal::service('serialization.phpserialize'), \Drupal::service(TimeInterface::class), 100);
    $fast_backend = new PhpBackend($bin, \Drupal::service('cache_tags.invalidator.checksum'), \Drupal::service(TimeInterface::class));
    $backend = new ChainedFastBackend($consistent_backend, $fast_backend, $bin);
    // Explicitly register the cache bin as it can not work through the
    // cache bin list in the container.
    \Drupal::service('cache_tags.invalidator')->addInvalidator($backend);
    return $backend;
  }

}
