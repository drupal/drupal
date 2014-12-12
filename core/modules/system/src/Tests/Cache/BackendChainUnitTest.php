<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\BackendChainUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\MemoryBackend;

/**
 * Unit test of the backend chain using the generic cache unit test base.
 *
 * @group Cache
 */
class BackendChainUnitTest extends GenericCacheBackendUnitTestBase {

  protected function createCacheBackend($bin) {
    $chain = new BackendChain($bin);

    // We need to create some various backends in the chain.
    $chain
      ->appendBackend(new MemoryBackend('foo'))
      ->prependBackend(new MemoryBackend('bar'))
      ->appendBackend(new MemoryBackend('baz'));

    return $chain;
  }

}
