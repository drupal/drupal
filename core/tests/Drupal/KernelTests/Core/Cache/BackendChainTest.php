<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\MemoryBackend;

/**
 * Unit test of the backend chain using the generic cache unit test base.
 *
 * @group Cache
 */
class BackendChainTest extends GenericCacheBackendUnitTestBase {

  protected function createCacheBackend($bin) {
    $chain = new BackendChain();

    // We need to create some various backends in the chain.
    $time = \Drupal::service(TimeInterface::class);
    $chain
      ->appendBackend(new MemoryBackend($time))
      ->prependBackend(new MemoryBackend($time))
      ->appendBackend(new MemoryBackend($time));

    \Drupal::service('cache_tags.invalidator')->addInvalidator($chain);

    return $chain;
  }

}
