<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\CacheTagsPurgeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\Core\Cache\CacheTagsInvalidator.
 */
#[CoversClass(CacheTagsInvalidator::class)]
#[Group('Cache')]
class CacheTagsInvalidatorTest extends UnitTestCase {

  /**
   * Tests invalidate tags with invalid tags.
   */
  public function testInvalidateTagsWithInvalidTags(): void {
    $cache_tags_invalidator = new CacheTagsInvalidator();
    $this->expectException(\AssertionError::class);
    $cache_tags_invalidator->invalidateTags(['node' => [2, 3, 5, 8, 13]]);
  }

  /**
   * Tests invalidate tags.
   *
   * @see ::invalidateTags
   * @see ::addInvalidator
   * @see ::addBin
   */
  public function testInvalidateTags(): void {
    $cache_tags_invalidator = new CacheTagsInvalidator();

    $invalidator_cache_bin = $this->createMock(InvalidatingCacheBackendInterface::class);
    $invalidator_cache_bin->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);
    $cache_tags_invalidator->addBin($invalidator_cache_bin);

    // We do not have to define that invalidateTags() is never called as the
    // interface does not define that method, trying to call it would result in
    // a fatal error.
    $non_invalidator_cache_bin = $this->createStub(CacheBackendInterface::class);
    $cache_tags_invalidator->addBin($non_invalidator_cache_bin);

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);
    $cache_tags_invalidator->addInvalidator($invalidator);

    $cache_tags_invalidator->invalidateTags(['node:1']);
  }

  /**
   * Tests injected iterators and runtime additions support repeated operations.
   */
  #[TestWith([FALSE])]
  #[TestWith([TRUE])]
  public function testIteratorsWithRuntimeAdditions(bool $add_at_runtime): void {
    $invalidators = [];
    for ($i = 0; $i < 2; $i++) {
      $invalidator = $this->createMock(ChecksumInvalidatorInterface::class);
      $invalidator->expects($this->exactly(2))->method('invalidateTags')->with(['node:1']);
      $invalidator->expects($this->exactly(2))->method('reset');
      $invalidator->expects($this->exactly(2))->method('purge');
      $invalidators[] = $invalidator;
    }
    $bins = [];
    for ($i = 0; $i < 3; $i++) {
      $bin = $this->createMock(InvalidatingCacheBackendInterface::class);
      $bin->expects($this->exactly(2))->method('invalidateTags')->with(['node:1']);
      $bins[] = $bin;
    }
    $non_invalidating_bin = $this->createStub(CacheBackendInterface::class);
    $plain_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $plain_invalidator->expects($this->exactly(2))->method('invalidateTags')->with(['node:1']);
    $injected_invalidators = [$invalidators[0], $plain_invalidator];
    $injected_bins = [$non_invalidating_bin, $bins[0]];
    if (!$add_at_runtime) {
      $injected_invalidators[] = $invalidators[1];
      $injected_bins[] = $bins[2];
    }
    $cache_tags_invalidator = new CacheTagsInvalidator(
      new \ArrayIterator($injected_invalidators),
      new \ArrayIterator($injected_bins),
      new \ArrayIterator([$non_invalidating_bin, $bins[1]]),
    );
    if ($add_at_runtime) {
      $cache_tags_invalidator->addInvalidator($invalidators[1]);
      $cache_tags_invalidator->addBin($bins[2]);
      $cache_tags_invalidator->addBin($non_invalidating_bin);
    }

    for ($i = 0; $i < 2; $i++) {
      $cache_tags_invalidator->invalidateTags(['node:1']);
      $cache_tags_invalidator->resetChecksums();
      $cache_tags_invalidator->purge();
    }
  }

}

/**
 * Test interface for testing the cache tags validator.
 */
interface InvalidatingCacheBackendInterface extends CacheTagsInvalidatorInterface, CacheBackendInterface {}

/**
 * A cache tags invalidator that supports checksum resets and purging.
 */
interface ChecksumInvalidatorInterface extends CacheTagsInvalidatorInterface, CacheTagsChecksumInterface, CacheTagsPurgeInterface {}
