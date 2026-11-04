<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PreWarm;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\PreWarm\CachePreWarmer;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\PreWarm\CachePreWarmer.
 */
#[CoversClass(CachePreWarmer::class)]
#[Group('PreWarm')]
class CachePreWarmerTest extends UnitTestCase {

  /**
   * The class resolver.
   */
  protected ClassResolverInterface&Stub $classResolver;

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
    $this->classResolver = $this->createStub(ClassResolverInterface::class);
    $this->warmedMap = new \SplObjectStorage();

    for ($i = 0; $i < 4; $i++) {
      $serviceId = 'service' . $i;
      $serviceMock = $this->createStub(PrewarmableInterface::class);
      $this->warmedMap[$serviceMock] = 0;

      $serviceMock->method('preWarm')
        ->willReturnCallback(function () use ($serviceMock): void {
          $this->warmedMap[$serviceMock] = 1 + $this->warmedMap[$serviceMock];
        });

      $returnMap[] = [$serviceId, $serviceMock];
    }

    $this->classResolver->method('getInstanceFromDefinition')
      ->willReturnMap($returnMap);
  }

  /**
   * Tests pre warm only one.
   *
   * @see ::preWarmOneCache
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
   * Tests pre warm by one.
   *
   * @see ::preWarmOneCache
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
   * Tests pre warm all.
   *
   * @see ::preWarmAllCaches
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
