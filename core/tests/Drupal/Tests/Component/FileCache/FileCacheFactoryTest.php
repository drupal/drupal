<?php

namespace Drupal\Tests\Component\FileCache;

use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\NullFileCache;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\FileCache\FileCacheFactory
 * @group FileCache
 */
class FileCacheFactoryTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $configuration = [
      'test_foo_settings' => [
        'collection' => 'test-23',
        'cache_backend_class' => '\Drupal\Tests\Component\FileCache\StaticFileCacheBackend',
        'cache_backend_configuration' => [
          'bin' => 'dog',
        ],
      ],
    ];
    FileCacheFactory::setConfiguration($configuration);
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $file_cache = FileCacheFactory::get('test_foo_settings', []);

    // Ensure the right backend and configuration is used.
    $filename = __DIR__ . '/Fixtures/llama-23.txt';
    $realpath = realpath($filename);
    $cid = 'prefix:test-23:' . $realpath;

    $file_cache->set($filename, 23);

    $static_cache = new StaticFileCacheBackend(['bin' => 'dog']);
    $result = $static_cache->fetch([$cid]);
    $this->assertNotEmpty($result);

    // Cleanup static caches.
    $file_cache->delete($filename);
  }

  /**
   * @covers ::get
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Required prefix configuration is missing
   */
  public function testGetNoPrefix() {
    FileCacheFactory::setPrefix(NULL);
    FileCacheFactory::get('test_foo_settings', []);
  }

  /**
   * @covers ::get
   */
  public function testGetDisabledFileCache() {
    // Ensure the returned FileCache is an instance of FileCache::class.
    $file_cache = FileCacheFactory::get('test_foo_settings', []);
    $this->assertInstanceOf(FileCache::class, $file_cache);

    $configuration = FileCacheFactory::getConfiguration();
    $configuration[FileCacheFactory::DISABLE_CACHE] = TRUE;
    FileCacheFactory::setConfiguration($configuration);

    // Ensure the returned FileCache is now an instance of NullFileCache::class.
    $file_cache = FileCacheFactory::get('test_foo_settings', []);
    $this->assertInstanceOf(NullFileCache::class, $file_cache);
  }


  /**
   * @covers ::getConfiguration
   * @covers ::setConfiguration
   */
  public function testGetSetConfiguration() {
    $configuration = FileCacheFactory::getConfiguration();
    $configuration['test_foo_bar'] = ['bar' => 'llama'];
    FileCacheFactory::setConfiguration($configuration);
    $configuration = FileCacheFactory::getConfiguration();
    $this->assertEquals(['bar' => 'llama'], $configuration['test_foo_bar']);
  }

  /**
   * @covers ::getPrefix
   * @covers ::setPrefix
   */
  public function testGetSetPrefix() {
    $prefix = $this->randomMachineName();
    FileCacheFactory::setPrefix($prefix);
    $this->assertEquals($prefix, FileCacheFactory::getPrefix());
  }

}
