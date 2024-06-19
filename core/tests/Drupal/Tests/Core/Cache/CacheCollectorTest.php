<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\CacheCollector
 * @group Cache
 */
class CacheCollectorTest extends UnitTestCase {

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * The lock backend that should be used.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * The cache id used for the test.
   *
   * @var string
   */
  protected $cid;

  /**
   * Cache collector implementation to test.
   *
   * @var \Drupal\Tests\Core\Cache\CacheCollectorHelper
   */
  protected $collector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheBackend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->cid = $this->randomMachineName();
    $this->collector = new CacheCollectorHelper($this->cid, $this->cacheBackend, $this->lock);

    $this->getContainerWithCacheTagsInvalidator($this->cacheTagsInvalidator);
  }

  /**
   * Tests the resolve cache miss function.
   */
  public function testResolveCacheMiss(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();
    $this->collector->setCacheMissData($key, $value);

    $this->assertEquals($value, $this->collector->get($key));
  }

  /**
   * Tests setting and getting values when the cache is empty.
   */
  public function testSetAndGet(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->assertNull($this->collector->get($key));

    $this->collector->set($key, $value);
    $this->assertTrue($this->collector->has($key));
    $this->assertEquals($value, $this->collector->get($key));
  }

  /**
   * Makes sure that NULL is a valid value and is collected.
   */
  public function testSetAndGetNull(): void {
    $key = $this->randomMachineName();
    $value = NULL;

    $this->cacheBackend->expects($this->once())
      ->method('invalidate')
      ->with($this->cid);
    $this->collector->set($key, $value);
    $this->assertTrue($this->collector->has($key));
    $this->assertEquals($value, $this->collector->get($key));

    // Ensure that getting a value that isn't set does not mark it as
    // existent.
    $non_existing_key = $this->randomMachineName(7);
    $this->collector->get($non_existing_key);
    $this->assertFalse($this->collector->has($non_existing_key));
  }

  /**
   * Tests returning value from the collected cache.
   */
  public function testGetFromCache(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $cache = (object) [
      'data' => [$key => $value],
      'created' => (int) $_SERVER['REQUEST_TIME'],
    ];
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with($this->cid)
      ->willReturn($cache);
    $this->assertTrue($this->collector->has($key));
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(0, $this->collector->getCacheMisses());
  }

  /**
   * Tests setting and deleting values.
   */
  public function testDelete(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->assertNull($this->collector->get($key));

    $this->collector->set($key, $value);
    $this->assertTrue($this->collector->has($key));
    $this->assertEquals($value, $this->collector->get($key));

    $this->cacheBackend->expects($this->once())
      ->method('invalidate')
      ->with($this->cid);
    $this->collector->delete($key);
    $this->assertFalse($this->collector->has($key));
    $this->assertEquals(NULL, $this->collector->get($key));
  }

  /**
   * Tests updating the cache when no changes were made.
   */
  public function testUpdateCacheNoChanges(): void {
    $this->lock->expects($this->never())
      ->method('acquire');
    $this->cacheBackend->expects($this->never())
      ->method('set');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests updating the cache after a set.
   */
  public function testUpdateCache(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->collector->setCacheMissData($key, $value);
    $this->collector->get($key);

    // Set up mock objects for the expected calls, first a lock acquire, then
    // cache get to look for conflicting cache entries, then a cache set and
    // finally the lock is released again.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with($this->cid, FALSE);
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with($this->cid, [$key => $value], Cache::PERMANENT, []);
    $this->lock->expects($this->once())
      ->method('release')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests updating the cache when the lock acquire fails.
   */
  public function testUpdateCacheLockFail(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->collector->setCacheMissData($key, $value);
    $this->collector->get($key);

    // The lock acquire returns false, so the method should abort.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(FALSE);
    $this->cacheBackend->expects($this->never())
      ->method('set');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests updating the cache when there is a conflict after cache invalidation.
   */
  public function testUpdateCacheInvalidatedConflict(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    // Set up mock cache get with conflicting entries.
    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with($this->cid)
      ->willReturnOnConsecutiveCalls(
        (object) [
          'data' => [$key => $value],
          'created' => (int) $_SERVER['REQUEST_TIME'],
        ],
        (object) [
          'data' => [$key => $value],
          'created' => (int) $_SERVER['REQUEST_TIME'] + 1,
        ],
      );

    $this->cacheBackend->expects($this->once())
      ->method('invalidate')
      ->with($this->cid);
    $this->collector->set($key, 'new value');

    // Set up mock objects for the expected calls, first a lock acquire, then
    // when cache get finds conflicting entries it deletes the cache and aborts.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $this->cacheBackend->expects($this->once())
      ->method('delete')
      ->with($this->cid);
    $this->lock->expects($this->once())
      ->method('release')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests a cache hit, then item updated by a different request.
   */
  public function testUpdateCacheMerge(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->collector->setCacheMissData($key, $value);
    $this->collector->get($key);

    // Set up mock objects for the expected calls, first a lock acquire, then
    // cache get to look for existing cache entries, which does find
    // and then it merges them.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $cache = (object) [
      'data' => ['other key' => 'other value'],
      'created' => (int) $_SERVER['REQUEST_TIME'] + 1,
    ];
    $this->collector->setCacheCreated($cache->created);
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with($this->cid)
      ->willReturn($cache);
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with($this->cid, ['other key' => 'other value', $key => $value], Cache::PERMANENT, []);
    $this->lock->expects($this->once())
      ->method('release')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests a cache miss, then item created by another request.
   */
  public function testUpdateCacheRace(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->collector->setCacheMissData($key, $value);
    $this->collector->get($key);

    // Set up mock objects for the expected calls, first a lock acquire, then
    // cache get to look for existing cache entries, which does find
    // and then it merges them.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $cache = (object) [
      'data' => ['other key' => 'other value'],
      'created' => (int) $_SERVER['REQUEST_TIME'] + 1,
    ];
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with($this->cid)
      ->willReturn($cache);

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests updating the cache after a delete.
   */
  public function testUpdateCacheDelete(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $cache = (object) [
      'data' => [$key => $value],
      'created' => (int) $_SERVER['REQUEST_TIME'],
    ];
    // Set up mock expectation, on the second call the with the second argument
    // set to TRUE because we triggered a cache invalidation.
    $allow_invalid = [FALSE, TRUE];
    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with($this->cid, $this->callback(function ($value) use (&$allow_invalid) {
        return array_shift($allow_invalid) === $value;
      }))
      ->willReturn($cache);

    $this->collector->delete($key);

    // Set up mock objects for the expected calls, first a lock acquire, then
    // a cache set and finally the lock is released again.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with($this->cid, [], Cache::PERMANENT, []);
    $this->lock->expects($this->once())
      ->method('release')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');

    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests a reset of the cache collector.
   */
  public function testUpdateCacheReset(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    // Set the data and request it.
    $this->collector->setCacheMissData($key, $value);
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals($value, $this->collector->get($key));

    // Should have been added to the storage and only be requested once.
    $this->assertEquals(1, $this->collector->getCacheMisses());

    // Reset the collected cache, should call it again.
    $this->collector->reset();
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(2, $this->collector->getCacheMisses());
  }

  /**
   * Tests a clear of the cache collector.
   */
  public function testUpdateCacheClear(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    // Set the data and request it.
    $this->collector->setCacheMissData($key, $value);
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals($value, $this->collector->get($key));

    // Should have been added to the storage and only be requested once.
    $this->assertEquals(1, $this->collector->getCacheMisses());

    // Clear the collected cache, should call it again.
    $this->cacheBackend->expects($this->once())
      ->method('delete')
      ->with($this->cid);
    $this->cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');
    $this->collector->clear();
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(2, $this->collector->getCacheMisses());
  }

  /**
   * Tests a clear of the cache collector using tags.
   */
  public function testUpdateCacheClearTags(): void {
    $key = $this->randomMachineName();
    $value = $this->randomMachineName();
    $tags = [$this->randomMachineName()];
    $this->collector = new CacheCollectorHelper($this->cid, $this->cacheBackend, $this->lock, $tags);

    // Set the data and request it.
    $this->collector->setCacheMissData($key, $value);
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals($value, $this->collector->get($key));

    // Should have been added to the storage and only be requested once.
    $this->assertEquals(1, $this->collector->getCacheMisses());

    // Clear the collected cache using the tags, should call it again.
    $this->cacheBackend->expects($this->never())
      ->method('delete');
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($tags);
    $this->collector->clear();
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(2, $this->collector->getCacheMisses());
  }

}
