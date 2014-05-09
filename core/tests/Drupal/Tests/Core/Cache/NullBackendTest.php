<?php

/**
 * @file
 * Definition of Drupal\Tests\Core\Cache\NullBackendTest.
 */

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\NullBackend;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cache NullBackend.
 *
 * @group Cache
 */
class NullBackendTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Cache NullBackend test',
      'description' => 'Tests the cache NullBackend.',
      'group' => 'Cache',
    );
  }

  /**
   * Tests that the NullBackend does not actually store variables.
   */
  function testNullBackend() {
    $null_cache = new NullBackend('test');

    $key = $this->randomName();
    $value = $this->randomName();

    $null_cache->set($key, $value);
    $this->assertFalse($null_cache->get($key));
  }
}
