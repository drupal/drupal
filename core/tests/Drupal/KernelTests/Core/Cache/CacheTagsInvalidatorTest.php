<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests lazy cache tag invalidation services.
 */
#[Group('Cache')]
#[RunTestsInSeparateProcesses]
class CacheTagsInvalidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    foreach (['cache_tags_invalidator', 'cache.bin', 'cache.bin.memory'] as $tag) {
      $container->register('test.' . $tag, DependentMemoryBackend::class)
        ->addArgument(new Reference('datetime.time'))
        ->addArgument(new Reference('cache_tags.invalidator'))
        ->addTag($tag);
    }
    $container->register('test.non_invalidating_bin', NullBackend::class)
      ->addArgument('test')
      ->addTag('cache.bin');
  }

  /**
   * Tests tagged services are only initialized when needed.
   */
  public function testLazyInvalidation(): void {
    $invalidator = $this->container->get('cache_tags.invalidator');
    $services = ['test.cache_tags_invalidator', 'test.cache.bin', 'test.cache.bin.memory', 'test.non_invalidating_bin'];
    foreach ($services as $id) {
      $this->assertFalse($this->container->initialized($id), $id);
    }

    $invalidator->invalidateTags(['test']);
    foreach ($services as $id) {
      $this->assertTrue($this->container->initialized($id), $id);
    }

    // The tagged iterators must be reusable on subsequent invalidations.
    for ($i = 0; $i < 2; $i++) {
      foreach (array_slice($services, 0, 3) as $id) {
        $this->container->get($id)->set('item', 'value', Cache::PERMANENT, ['test']);
        $this->assertNotFalse($this->container->get($id)->get('item'));
      }
      $invalidator->invalidateTags(['test']);
      foreach (array_slice($services, 0, 3) as $id) {
        $this->assertFalse($this->container->get($id)->get('item'));
      }
    }
  }

  /**
   * Tests checksum operations do not initialize cache bins.
   */
  #[TestWith(['resetChecksums'])]
  #[TestWith(['purge'])]
  public function testLazyChecksumOperation(string $method): void {
    $invalidator = $this->container->get('cache_tags.invalidator');
    $this->assertFalse($this->container->initialized('test.cache_tags_invalidator'));
    for ($i = 0; $i < 2; $i++) {
      $invalidator->$method();
      $this->assertTrue($this->container->initialized('test.cache_tags_invalidator'));
      $this->assertFalse($this->container->initialized('test.cache.bin'));
      $this->assertFalse($this->container->initialized('test.cache.bin.memory'));
      $this->assertFalse($this->container->initialized('test.non_invalidating_bin'));
    }
  }

}

/**
 * A cache backend that depends on the cache tags invalidator.
 */
class DependentMemoryBackend extends MemoryBackend {

  /**
   * Constructs a DependentMemoryBackend object.
   */
  public function __construct(TimeInterface $time, protected CacheTagsInvalidatorInterface $invalidator) {
    parent::__construct($time);
  }

}
