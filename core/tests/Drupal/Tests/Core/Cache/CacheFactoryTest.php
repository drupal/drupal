<?php

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheFactory
 * @group Cache
 */
class CacheFactoryTest extends UnitTestCase {

  /**
   * Test that the cache factory falls back to the built-in default service.
   *
   * @covers ::__construct
   * @covers ::get
   */
  public function testCacheFactoryWithDefaultSettings() {
    $settings = new Settings([]);
    $cache_factory = new CacheFactory($settings);

    $container = new ContainerBuilder();
    $cache_factory->setContainer($container);

    $builtin_default_backend_factory = $this->createMock('\Drupal\Core\Cache\CacheFactoryInterface');
    $container->set('cache.backend.database', $builtin_default_backend_factory);

    $render_bin = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $builtin_default_backend_factory->expects($this->once())
      ->method('get')
      ->with('render')
      ->will($this->returnValue($render_bin));

    $actual_bin = $cache_factory->get('render');
    $this->assertSame($render_bin, $actual_bin);
  }

  /**
   * Test that the cache factory falls back to customized default service.
   *
   * @covers ::__construct
   * @covers ::get
   */
  public function testCacheFactoryWithCustomizedDefaultBackend() {
    $settings = new Settings([
      'cache' => [
        'default' => 'cache.backend.custom',
      ],
    ]);
    $cache_factory = new CacheFactory($settings);

    $container = new ContainerBuilder();
    $cache_factory->setContainer($container);

    $custom_default_backend_factory = $this->createMock('\Drupal\Core\Cache\CacheFactoryInterface');
    $container->set('cache.backend.custom', $custom_default_backend_factory);

    $render_bin = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $custom_default_backend_factory->expects($this->once())
      ->method('get')
      ->with('render')
      ->will($this->returnValue($render_bin));

    $actual_bin = $cache_factory->get('render');
    $this->assertSame($render_bin, $actual_bin);
  }

  /**
   * Test that the cache factory uses the correct default bin backend.
   *
   * @covers ::__construct
   * @covers ::get
   */
  public function testCacheFactoryWithDefaultBinBackend() {
    // Ensure the default bin backends are used before the configured default.
    $settings = new Settings([
      'cache' => [
        'default' => 'cache.backend.unused',
      ],
    ]);

    $default_bin_backends = [
      'render' => 'cache.backend.custom',
    ];

    $cache_factory = new CacheFactory($settings, $default_bin_backends);

    $container = new ContainerBuilder();
    $cache_factory->setContainer($container);

    $custom_default_backend_factory = $this->createMock('\Drupal\Core\Cache\CacheFactoryInterface');
    $container->set('cache.backend.custom', $custom_default_backend_factory);

    $render_bin = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $custom_default_backend_factory->expects($this->once())
      ->method('get')
      ->with('render')
      ->will($this->returnValue($render_bin));

    $actual_bin = $cache_factory->get('render');
    $this->assertSame($render_bin, $actual_bin);
  }

  /**
   * Test that the cache factory picks the correct per-bin service.
   *
   * @covers ::__construct
   * @covers ::get
   */
  public function testCacheFactoryWithSpecifiedPerBinBackend() {
    // Ensure the per-bin configuration is used before the configured default
    // and per-bin defaults.
    $settings = new Settings([
      'cache' => [
        'default' => 'cache.backend.unused',
        'bins' => [
          'render' => 'cache.backend.custom',
        ],
      ],
    ]);

    $default_bin_backends = [
      'render' => 'cache.backend.unused',
    ];

    $cache_factory = new CacheFactory($settings, $default_bin_backends);

    $container = new ContainerBuilder();
    $cache_factory->setContainer($container);

    $custom_render_backend_factory = $this->createMock('\Drupal\Core\Cache\CacheFactoryInterface');
    $container->set('cache.backend.custom', $custom_render_backend_factory);

    $render_bin = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $custom_render_backend_factory->expects($this->once())
      ->method('get')
      ->with('render')
      ->will($this->returnValue($render_bin));

    $actual_bin = $cache_factory->get('render');
    $this->assertSame($render_bin, $actual_bin);
  }

}
