<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\PhpBackend;

/**
 * Unit test of the PHP cache backend using the generic cache unit test base.
 *
 * @group Cache
 */
class PhpBackendTest extends GenericCacheBackendUnitTestBase {

  /**
   * Creates a new instance of MemoryBackend.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   A new PhpBackend object.
   */
  protected function createCacheBackend($bin) {
    return new PhpBackend($bin, \Drupal::service('cache_tags.invalidator.checksum'), \Drupal::service(TimeInterface::class));
  }

}
