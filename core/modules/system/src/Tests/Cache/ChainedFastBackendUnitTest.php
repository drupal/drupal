<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\ChainedFastBackendUnitTest.
 */

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
    $consistent_backend = new DatabaseBackend($this->container->get('database'), $bin);
    $fast_backend = new PhpBackend($bin);
    return new ChainedFastBackend($consistent_backend, $fast_backend, $bin);
  }

}
