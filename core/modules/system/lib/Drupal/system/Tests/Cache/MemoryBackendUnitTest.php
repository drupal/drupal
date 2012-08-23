<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\ArrayBackendUnitTest.
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

  protected function createCacheBackend($bin) {
    return new MemoryBackend($bin);
  }
}
