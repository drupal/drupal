<?php

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\NullBackend;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cache NullBackend.
 *
 * @group Cache
 */
class NullBackendTest extends UnitTestCase {

  /**
   * Tests that the NullBackend does not actually store variables.
   */
  public function testNullBackend() {
    $null_cache = new NullBackend('test');

    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $null_cache->set($key, $value);
    $this->assertFalse($null_cache->get($key));
  }

}
