<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\FileCache;

use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\NullFileCache;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Utility\Random;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\FileCache\FileCacheFactory
 * @group FileCache
 */
class FileCacheFactoryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testGet(): void {
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
   */
  public function testGetNoPrefix(): void {
    FileCacheFactory::setPrefix(NULL);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Required prefix configuration is missing');
    FileCacheFactory::get('test_foo_settings', []);
  }

  /**
   * @covers ::get
   */
  public function testGetDisabledFileCache(): void {
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
   * @covers ::get
   *
   * @dataProvider configurationDataProvider
   */
  public function testGetConfigurationOverrides($configuration, $arguments, $class): void {
    FileCacheFactory::setConfiguration($configuration);

    $file_cache = FileCacheFactory::get('test_foo_settings', $arguments);
    $this->assertInstanceOf($class, $file_cache);
  }

  /**
   * Data provider for testGetConfigurationOverrides().
   */
  public static function configurationDataProvider() {
    $data = [];

    // Test fallback configuration.
    $data['fallback-configuration'] = [
      [],
      [],
      FileCache::class,
    ];

    // Test default configuration.
    $data['default-configuration'] = [
      ['default' => ['class' => CustomFileCache::class]],
      [],
      CustomFileCache::class,
    ];

    // Test specific per collection setting.
    $data['collection-setting'] = [
      ['test_foo_settings' => ['class' => CustomFileCache::class]],
      [],
      CustomFileCache::class,
    ];

    // Test default configuration plus specific per collection setting.
    $data['default-plus-collection-setting'] = [
      [
        'default' => ['class' => '\stdClass'],
        'test_foo_settings' => ['class' => CustomFileCache::class],
      ],
      [],
      CustomFileCache::class,
    ];

    // Test default configuration plus class specific override.
    $data['default-plus-class-override'] = [
      ['default' => ['class' => '\stdClass']],
      ['class' => CustomFileCache::class],
      CustomFileCache::class,
    ];

    // Test default configuration plus class specific override plus specific
    // per collection setting.
    $data['default-plus-class-plus-collection-setting'] = [
      [
        'default' => ['class' => '\stdClass'],
        'test_foo_settings' => ['class' => CustomFileCache::class],
      ],
      ['class' => '\stdClass'],
      CustomFileCache::class,
    ];

    return $data;
  }

  /**
   * @covers ::getConfiguration
   * @covers ::setConfiguration
   */
  public function testGetSetConfiguration(): void {
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
  public function testGetSetPrefix(): void {
    // Random generator.
    $random = new Random();

    $prefix = $random->name(8, TRUE);
    FileCacheFactory::setPrefix($prefix);
    $this->assertEquals($prefix, FileCacheFactory::getPrefix());
  }

}

/**
 * Class to help test the file cache class.
 */
class CustomFileCache extends FileCache {}
