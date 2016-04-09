<?php

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Core\Cache\CacheCollectorHelper;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests DatabaseBackend cache tag implementation.
 *
 * @group Cache
 */
class CacheCollectorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Change container to database cache backends.
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\CacheFactory')
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', [new Reference('service_container')]);

    // Change container to use database lock backends.
    $container
      ->register('lock', 'Drupal\Core\Lock\DatabaseLockBackend')
      ->addArgument(new Reference('database'));
  }

  /**
   * Tests setting and invalidating
   *
   * @dataProvider providerTestInvalidCharacters
   */
  public function testCacheCollector($cid, $key, $value) {
    $collector = new CacheCollectorHelper($cid, $this->container->get('cache.default'), $this->container->get('lock'));
    $this->assertNull($collector->get($key));
    $collector->set($key, $value);
    $this->assertEquals($value, $collector->get($key));
    $collector->destruct();
    // @todo Shouldn't this be empty after destruction?
    $this->assertEquals($value, $collector->get($key));
  }

  /**
   * Data provider for ::testCacheCollector().
   */
  public function providerTestInvalidCharacters() {
    return [
      // Nothing special.
      ['foo', 'bar', 'baz'],
      // Invalid characters in CID.
      ['éøïвβ中國書۞', 'foo', 'bar'],
      // Really long CID.
      [$this->randomString(1024), 'foo', 'bar'],
    ];
  }

}
