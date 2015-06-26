<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\CachedStorageTest.
 */

namespace Drupal\Tests\Core\Config;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Cache\NullBackend;

/**
 * Tests the interaction of cache and file storage in CachedStorage.
 *
 * @group Config
 */
class CachedStorageTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Cache\CacheFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheFactory;

  /**
   * Test listAll static cache.
   */
  public function testListAllStaticCache() {
    $prefix = __FUNCTION__;
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');

    $response = array("$prefix." . $this->randomMachineName(), "$prefix." . $this->randomMachineName());
    $storage->expects($this->once())
      ->method('listAll')
      ->with($prefix)
      ->will($this->returnValue($response));

    $cache = new NullBackend(__FUNCTION__);

    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
  }

}
