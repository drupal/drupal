<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\NullBackendTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\NullBackend;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests the cache NullBackend.
 */
class NullBackendTest extends UnitTestBase {
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
    $this->assertTrue($null_cache->isEmpty());
    $this->assertFalse($null_cache->get($key));
  }
}
