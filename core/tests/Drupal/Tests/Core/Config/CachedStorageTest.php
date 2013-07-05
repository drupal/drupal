<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Tests the interaction of cache and file storage in CachedStorage.
 *
 * @group Config
 */
class CachedStorageTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Config cached storage test',
      'description' => 'Tests the interaction of cache and file storage in CachedStorage.',
      'group' => 'Configuration'
    );
  }

  /**
   * Test listAll static cache.
   */
  public function testListAllStaticCache() {
    $prefix = __FUNCTION__;
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');

    $response = array("$prefix." . $this->randomName(), "$prefix." . $this->randomName());
    $storage->expects($this->once())
      ->method('listAll')
      ->with($prefix)
      ->will($this->returnValue($response));

    $cache = new NullBackend(__FUNCTION__);
    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
  }

  /**
   * Test CachedStorage::listAll() persistent cache.
   */
  public function testListAllPrimedPersistentCache() {
    $prefix = __FUNCTION__;
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->never())->method('listAll');

    $response = array("$prefix." . $this->randomName(), "$prefix." . $this->randomName());
    $cache = new MemoryBackend(__FUNCTION__);
    $cache->set('find:' . $prefix, $response);
    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($response, $cachedStorage->listAll($prefix));
  }

  /**
   * Test that we don't fall through to file storage with a primed cache.
   */
  public function testGetMultipleOnPrimedCache() {
    $configNames = array(
      'foo.bar',
      'baz.back',
    );
    $configCacheValues = array(
      'foo.bar' => (object) array(
        'data' => array('foo' => 'bar'),
      ),
      'baz.back' => (object) array(
        'data' => array('foo' => 'bar'),
      ),
    );
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->never())->method('readMultiple');
    $cache = new MemoryBackend(__FUNCTION__);
    foreach ($configCacheValues as $key => $value) {
      $cache->set($key, $value);
    }
    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($configCacheValues, $cachedStorage->readMultiple($configNames));
  }

  /**
   * Test fall through to file storage on a cache miss.
   */
  public function testGetMultipleOnPartiallyPrimedCache() {
    $configNames = array(
      'foo.bar',
      'baz.back',
      $this->randomName() . '. ' . $this->randomName(),
    );
    $configCacheValues = array(
      'foo.bar' => (object) array(
        'data' => array('foo' => 'bar'),
      ),
      'baz.back' => (object) array(
        'data' => array('foo' => 'bar'),
      ),
    );
    $cache = new MemoryBackend(__FUNCTION__);
    foreach ($configCacheValues as $key => $value) {
      $cache->set($key, $value);
    }

    $response = array($configNames[2] => array($this->randomName()));
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->once())
      ->method('readMultiple')
      ->with(array(2 => $configNames[2]))
      ->will($this->returnValue($response));

    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertEquals($configCacheValues + $response, $cachedStorage->readMultiple($configNames));
  }
}
