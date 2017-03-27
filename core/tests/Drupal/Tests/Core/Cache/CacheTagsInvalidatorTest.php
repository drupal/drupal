<?php

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheTagsInvalidator
 * @group Cache
 */
class CacheTagsInvalidatorTest extends UnitTestCase {

  /**
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsWithInvalidTags() {
    $cache_tags_invalidator = new CacheTagsInvalidator();
    $this->setExpectedException(\AssertionError::class);
    $cache_tags_invalidator->invalidateTags(['node' => [2, 3, 5, 8, 13]]);
  }

  /**
   * @covers ::invalidateTags
   * @covers ::addInvalidator
   */
  public function testInvalidateTags() {
    $cache_tags_invalidator = new CacheTagsInvalidator();

    // This does not actually implement,
    // \Drupal\Cache\Cache\CacheBackendInterface but we can not mock from two
    // interfaces, we would need a test class for that.
    $invalidator_cache_bin = $this->getMock('\Drupal\Core\Cache\CacheTagsInvalidator');
    $invalidator_cache_bin->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);

    // We do not have to define that invalidateTags() is never called as the
    // interface does not define that method, trying to call it would result in
    // a fatal error.
    $non_invalidator_cache_bin = $this->getMock('\Drupal\Core\Cache\CacheBackendInterface');

    $container = new Container();
    $container->set('cache.invalidator_cache_bin', $invalidator_cache_bin);
    $container->set('cache.non_invalidator_cache_bin', $non_invalidator_cache_bin);
    $container->setParameter('cache_bins', ['cache.invalidator_cache_bin' => 'invalidator_cache_bin', 'cache.non_invalidator_cache_bin' => 'non_invalidator_cache_bin']);
    $cache_tags_invalidator->setContainer($container);

    $invalidator = $this->getMock('\Drupal\Core\Cache\CacheTagsInvalidator');
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node:1']);

    $cache_tags_invalidator->addInvalidator($invalidator);

    $cache_tags_invalidator->invalidateTags(['node:1']);
  }

}
