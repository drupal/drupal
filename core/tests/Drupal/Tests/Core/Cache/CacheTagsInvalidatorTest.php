<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheTagsInvalidator
 * @group Cache
 */
class CacheTagsInvalidatorTest extends UnitTestCase {

  /**
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsWithInvalidTags(): void {
    $cache_tags_invalidator = new CacheTagsInvalidator();
    $this->expectException(\AssertionError::class);
    $cache_tags_invalidator->invalidateTags(['node' => [2, 3, 5, 8, 13]]);
  }

  /**
   * @covers ::invalidateTags
   * @covers ::addInvalidator
   * @covers ::addBin
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
    $non_invalidator_cache_bin = $this->createMock(CacheBackendInterface::class);
    $cache_tags_invalidator->addBin($non_invalidator_cache_bin);

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);
    $cache_tags_invalidator->addInvalidator($invalidator);

    $cache_tags_invalidator->invalidateTags(['node:1']);
  }

}

/**
 * Test interface for testing the cache tags validator.
 */
interface InvalidatingCacheBackendInterface extends CacheTagsInvalidatorInterface, CacheBackendInterface {}
