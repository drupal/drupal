<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\ChainedFastBackendFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\ChainedFastBackendFactory
 * @group Cache
 */
class ChainedFastBackendFactoryTest extends UnitTestCase {

  /**
   * Test if the same name is provided for consistent and fast services.
   */
  public function testIdenticalService() {
    $container = $this->createMock(ContainerInterface::class);
    $testCacheFactory = $this->createMock(CacheFactoryInterface::class);
    $testCacheBackend = $this->createMock(CacheBackendInterface::class);

    $container->expects($this->once())
      ->method('get')
      ->with('cache.backend.test')
      ->willReturn($testCacheFactory);

    $testCacheFactory->expects($this->once())
      ->method('get')
      ->with('test_bin')
      ->willReturn($testCacheBackend);

    $cacheFactory = new ChainedFastBackendFactory(NULL, 'cache.backend.test', 'cache.backend.test');
    $cacheFactory->setContainer($container);

    $cacheBackend = $cacheFactory->get('test_bin');

    // The test backend should be returned directly.
    $this->assertSame($testCacheBackend, $cacheBackend);
  }

  /**
   * Test if different names are provided for consistent and fast services.
   */
  public function testDifferentServices() {
    $container = $this->createMock(ContainerInterface::class);
    $testConsistentCacheFactory = $this->createMock(CacheFactoryInterface::class);
    $testFastCacheFactory = $this->createMock(CacheFactoryInterface::class);
    $testConsistentCacheBackend = $this->createMock(CacheBackendInterface::class);
    $testFastCacheBackend = $this->createMock(CacheBackendInterface::class);

    $container->expects($this->exactly(2))
      ->method('get')
      ->will(
        $this->returnCallback(function ($service) use ($testFastCacheFactory, $testConsistentCacheFactory) {
          return match ($service) {
            'cache.backend.test_consistent' => $testConsistentCacheFactory,
            'cache.backend.test_fast' => $testFastCacheFactory,
          };
        })
      );

    // The same bin should be retrieved from both backends.
    $testConsistentCacheFactory->expects($this->once())
      ->method('get')
      ->with('test_bin')
      ->willReturn($testConsistentCacheBackend);
    $testFastCacheFactory->expects($this->once())
      ->method('get')
      ->with('test_bin')
      ->willReturn($testFastCacheBackend);

    $cacheFactory = new ChainedFastBackendFactory(NULL, 'cache.backend.test_consistent', 'cache.backend.test_fast');
    $cacheFactory->setContainer($container);

    // A wrapping ChainedFastBackend should be returned.
    $cacheBackend = $cacheFactory->get('test_bin');
    $this->assertInstanceOf(ChainedFastBackend::class, $cacheBackend);
  }

}
