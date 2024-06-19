<?php

declare(strict_types=1);

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
   * @var \Drupal\Core\Cache\CacheFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheFactory;

  /**
   * Tests listAll static cache.
   */
  public function testListAllStaticCache(): void {
    $prefix = __FUNCTION__;
    $storage = $this->createMock('Drupal\Core\Config\StorageInterface');

    $response = ["$prefix." . $this->randomMachineName(), "$prefix." . $this->randomMachineName()];
    $storage->expects($this->once())
      ->method('listAll')
      ->with($prefix)
      ->willReturn($response);

    $cache = new NullBackend(__FUNCTION__);

    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
  }

}
