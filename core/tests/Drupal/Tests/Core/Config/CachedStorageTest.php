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
      'foo.bar' => array(
        'foo' => 'bar',
      ),
      'baz.back' => array(
        'foo' => 'bar',
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
   * Test fall through to file storage in CachedStorage::readMulitple().
   */
  public function testGetMultipleOnPartiallyPrimedCache() {
    $configNames = array(
      'foo.bar',
      'baz.back',
      'config.exists_not_cached',
      'config.does_not_exist_cached',
      'config.does_not_exist',
    );
    $configCacheValues = array(
      'foo.bar' => array(
        'foo' => 'bar',
      ),
      'baz.back' => array(
        'foo' => 'bar',
      ),
    );
    $cache = new MemoryBackend(__FUNCTION__);
    foreach ($configCacheValues as $key => $value) {
      $cache->set($key, $value);
    }
    $cache->set('config.does_not_exist_cached', FALSE);

    $config_exists_not_cached_data = array('foo' => 'bar');
    $response = array(
      $configNames[2] => $config_exists_not_cached_data,
      $configNames[4] => FALSE,
    );
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->once())
      ->method('readMultiple')
      ->with(array(2 => $configNames[2], 4 => $configNames[4]))
      ->will($this->returnValue($response));

    $cachedStorage = new CachedStorage($storage, $cache);
    $expected_data = $configCacheValues + array($configNames[2] => $config_exists_not_cached_data);
    $this->assertEquals($expected_data, $cachedStorage->readMultiple($configNames));

    // Ensure that the a missing file is cached.
    $entry = $cache->get('config.does_not_exist');
    $this->assertFalse($entry->data);

    // Ensure that the a file containing data is cached.
    $entry = $cache->get('config.exists_not_cached');
    $this->assertEquals($config_exists_not_cached_data, $entry->data);
  }

  /**
   * Test fall through to file storage on a cache miss in CachedStorage::read().
   */
  public function testReadNonExistentFileCacheMiss() {
    $name = 'config.does_not_exist';
    $cache = new MemoryBackend(__FUNCTION__);
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->once())
            ->method('read')
            ->with($name)
            ->will($this->returnValue(FALSE));
    $cachedStorage = new CachedStorage($storage, $cache);

    $this->assertFalse($cachedStorage->read($name));

    // Ensure that the a missing file is cached.
    $entry = $cache->get('config.does_not_exist');
    $this->assertFalse($entry->data);
  }

  /**
   * Test file storage on a cache hit in CachedStorage::read().
   */
  public function testReadNonExistentFileCached() {
    $name = 'config.does_not_exist';
    $cache = new MemoryBackend(__FUNCTION__);
    $cache->set($name, FALSE);

    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $storage->expects($this->never())
            ->method('read');
    $cachedStorage = new CachedStorage($storage, $cache);
    $this->assertFalse($cachedStorage->read($name));
  }

}
