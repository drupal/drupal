<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\MemoryBackendUnitTest.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\MemoryBackend;

/**
 * Tests MemoryBackend using GenericCacheBackendUnitTestBase.
 */
class MemoryBackendUnitTest extends GenericCacheBackendUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Memory cache backend',
      'description' => 'Unit test of the memory cache backend using the generic cache unit test base.',
      'group' => 'Cache',
    );
  }

  /**
   * Creates a new instance of MemoryBackend.
   *
   * @return
   *   A new MemoryBackend object.
   */
  protected function createCacheBackend($bin) {
    return new MemoryBackend($bin);
  }
}
