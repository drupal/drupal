<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PreWarm;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\PreWarm\CachePreWarmer;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\PreWarm\CachePreWarmer
 * @group PreWarm
 */
class CachePreWarmerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MockObject|ClassResolverInterface $classResolver;

  /**
   * @var \SplObjectStorage<\Drupal\Core\PreWarm\PreWarmableInterface|\PHPUnit\Framework\MockObject\MockObject>
   */
  protected \SplObjectStorage $warmedMap;

  public function testNoServices(): void {
    $classResolver = $this->createMock(ClassResolverInterface::class);
    $classResolver->expects($this->never())
      ->method('getInstanceFromDefinition');

    $prewarmer = new CachePreWarmer($classResolver, []);

    $this->assertFalse($prewarmer->preWarmOneCache());
    $this->assertFalse($prewarmer->preWarmAllCaches());
  }

  protected function setupCacheServices(): void {
    $this->classResolver = $this->createMock(ClassResolverInterface::class);
    $this->warmedMap = new \SplObjectStorage();

    for ($i = 0; $i < 4; $i++) {
      $serviceId = 'service' . $i;
      $serviceMock = $this->createMock(PrewarmableInterface::class);
      $this->warmedMap[$serviceMock] = 0;

      $serviceMock->method('preWarm')
        ->willReturnCallback(function () use ($serviceMock) {
          $this->warmedMap[$serviceMock] = 1 + $this->warmedMap[$serviceMock];
        });

      $returnMap[] = [$serviceId, $serviceMock];
    }

    $this->classResolver->method('getInstanceFromDefinition')
      ->willReturnMap($returnMap);
  }

  /**
   * @covers ::preWarmOneCache
   */
  public function testPreWarmOnlyOne(): void {
    $this->setupCacheServices();

    $preWarmer = new CachePreWarmer($this->classResolver, ['service0', 'service1', 'service2', 'service3']);

    $this->assertTrue($preWarmer->preWarmOneCache());

    $warmed = 0;
    foreach ($this->warmedMap as $service) {
      $warmed += $this->warmedMap[$service];
    }
    $this->assertEquals(1, $warmed);
  }

  /**
   * @covers ::preWarmOneCache
   */
  public function testPreWarmByOne(): void {
    $this->setupCacheServices();

    $preWarmer = new CachePreWarmer($this->classResolver, ['service0', 'service1', 'service2', 'service3']);

    while ($preWarmer->preWarmOneCache()) {

    }

    foreach ($this->warmedMap as $service) {
      $this->assertEquals(1, $this->warmedMap[$service]);
    }
  }

  /**
   * @covers ::preWarmAllCaches
   */
  public function testPreWarmAll(): void {
    $this->setupCacheServices();

    $preWarmer = new CachePreWarmer($this->classResolver, ['service0', 'service1', 'service2', 'service3']);

    $this->assertTrue($preWarmer->preWarmAllCaches());

    foreach ($this->warmedMap as $service) {
      $this->assertEquals(1, $this->warmedMap[$service]);
    }

    $this->assertFalse($preWarmer->preWarmAllCaches());
  }

}
