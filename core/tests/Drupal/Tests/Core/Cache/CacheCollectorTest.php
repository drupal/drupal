<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Drupal\Core\Cache\CacheCollector.
 */
#[CoversClass(CacheCollector::class)]
#[Group('Cache')]
class CacheCollectorTest extends UnitTestCase {

  /**
   * The cache backend that should be used.
   */
  protected CacheBackendInterface $cacheBackend;

  /**
   * The lock backend that should be used.
   */
  protected LockBackendInterface $lock;

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

    $this->cacheBackend = $this->createStub(CacheBackendInterface::class);
    $this->lock = $this->createStub(LockBackendInterface::class);
    $this->cid = $this->randomMachineName();
    $this->collector = new CacheCollectorHelper($this->cid, $this->cacheBackend, $this->lock);
  }

  /**
   * Reinitializes the cache backend as a mock object.
   */
  protected function setUpMockCacheBackend(): void {
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
    $reflection = new \ReflectionProperty($this->collector, 'cache');
    $reflection->setValue($this->collector, $this->cacheBackend);
  }

  /**
   * Reinitializes the lock backend as a mock object.
   */
  protected function setUpMockLockBackend(): void {
    $this->lock = $this->createMock(LockBackendInterface::class);
    $reflection = new \ReflectionProperty($this->collector, 'lock');
    $reflection->setValue($this->collector, $this->lock);
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
    $this->setUpMockCacheBackend();

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
    $this->setUpMockCacheBackend();

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
    $this->setUpMockCacheBackend();

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
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

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
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

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
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

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
   * Tests setting to cache when there is a conflict after cache invalidation.
   */
  #[TestWith([TRUE, TRUE, TRUE, TRUE])]
  #[TestWith([TRUE, TRUE, TRUE, FALSE])]
  #[TestWith([TRUE, TRUE, FALSE, NULL])]
  #[TestWith([TRUE, FALSE, FALSE, NULL])]
  #[TestWith([TRUE, FALSE, TRUE, FALSE])]
  #[TestWith([FALSE, FALSE, FALSE, NULL])]
  #[TestWith([FALSE, TRUE, FALSE, NULL])]
  #[TestWith([FALSE, FALSE, TRUE, FALSE])]
  #[TestWith([FALSE, TRUE, TRUE, TRUE])]
  #[TestWith([FALSE, TRUE, TRUE, FALSE])]
  public function testSetCacheInvalidatedConflict(bool $lock_acquired, bool $start_cache_item, bool $end_cache_item, ?bool $timestamp_matches): void {

    if ($end_cache_item === FALSE && isset($timestamp_matches)) {
      throw new \BadMethodCallException('timestamp_matches is ignored when end_cache_item is FALSE');
    }
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    // Set up mock cache get with conflicting entries.
    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with($this->cid)
      ->willReturnOnConsecutiveCalls(
        $start_cache_item ? (object) [
          'data' => [$key => $value],
          'created' => (int) $_SERVER['REQUEST_TIME'],
        ] : FALSE,
        $end_cache_item ? (object) [
          'data' => [$key => $value],
          'created' => (int) $_SERVER['REQUEST_TIME'] + ($timestamp_matches ? 0 : 1),
        ] : FALSE,
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
      ->willReturn($lock_acquired);
    if ($end_cache_item) {
      $this->cacheBackend->expects($this->once())
        ->method('delete')
        ->with($this->cid);
    }
    if ($lock_acquired) {
      $this->lock->expects($this->once())
        ->method('release')
        ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');
    }
    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests updating the cache when there is a conflict after cache invalidation.
   */
  #[TestWith([TRUE, TRUE, TRUE, TRUE])]
  #[TestWith([TRUE, TRUE, TRUE, FALSE])]
  #[TestWith([TRUE, TRUE, FALSE, NULL])]
  #[TestWith([TRUE, FALSE, FALSE, NULL])]
  #[TestWith([TRUE, FALSE, TRUE, FALSE])]
  #[TestWith([FALSE, FALSE, FALSE, NULL])]
  #[TestWith([FALSE, TRUE, FALSE, NULL])]
  #[TestWith([FALSE, FALSE, TRUE, FALSE])]
  #[TestWith([FALSE, TRUE, TRUE, TRUE])]
  #[TestWith([FALSE, TRUE, TRUE, FALSE])]
  public function testUpdateCacheConflict(bool $lock_acquired, bool $start_cache_item, bool $end_cache_item, ?bool $content_matches): void {

    if ($end_cache_item === FALSE && isset($content_matches)) {
      throw new \BadMethodCallException('content_matches is ignored when end_cache_item is FALSE');
    }
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

    $key = $this->randomMachineName();
    $value = $this->randomMachineName();

    $this->collector->setCacheMissData($key, $value);
    $this->collector->setCacheMissData('another key', 'another value');

    // Set up mock cache get with conflicting entries. The item present when the
    // cache is written contains the same data as the item loaded at the start
    // of the request only when $content_matches is TRUE; otherwise the data
    // differs, producing a different fingerprint.
    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with($this->cid)
      ->willReturnOnConsecutiveCalls(
        $start_cache_item ? (object) [
          'data' => ['conflicting' => 'original'],
          'created' => (int) $_SERVER['REQUEST_TIME'],
        ] : FALSE,
        $end_cache_item ? (object) [
          'data' => ['conflicting' => $content_matches ? 'original' : 'changed'],
          'created' => (int) $_SERVER['REQUEST_TIME'],
        ] : FALSE,
      );

    $this->collector->get($key);

    // When the cache is being warmed, if the lock can't be acquired, or if the
    // cache item has changed during the request, nothing should be set.
    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn($lock_acquired);
    if ($end_cache_item && !$content_matches) {
      $this->cacheBackend->expects($this->never())
        ->method('set')
        ->with($this->cid);
    }
    if ($lock_acquired) {
      $this->lock->expects($this->once())
        ->method('release')
        ->with($this->cid . ':Drupal\Core\Cache\CacheCollector');
    }
    $this->cacheBackend->expects($this->never())
      ->method('delete');
    // Destruct the object to trigger the update data process.
    $this->collector->destruct();
  }

  /**
   * Tests that a concurrent write in the same millisecond is detected.
   *
   * The previous implementation compared the cache item's millisecond-precision
   * creation timestamp, which could not distinguish two writes that happened
   * within the same millisecond. The content fingerprint detects this case:
   * here the item loaded at the start of the request and the item present when
   * the cache is written share an identical creation timestamp but contain
   * different data, so the write must back out rather than overwrite a
   * concurrent request's data.
   */
  public function testUpdateCacheConcurrentWriteSameTimestamp(): void {
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

    $key = $this->randomMachineName();
    $value = $this->randomMachineName();
    $created = (int) $_SERVER['REQUEST_TIME'];

    $this->collector->setCacheMissData($key, $value);

    $this->cacheBackend->expects($this->exactly(2))
      ->method('get')
      ->with($this->cid)
      ->willReturnOnConsecutiveCalls(
        (object) [
          'data' => ['shared' => 'original'],
          'created' => $created,
        ],
        (object) [
          'data' => ['shared' => 'concurrent'],
          'created' => $created,
        ],
      );

    $this->collector->get($key);

    $this->lock->expects($this->once())
      ->method('acquire')
      ->with($this->cid . ':Drupal\Core\Cache\CacheCollector')
      ->willReturn(TRUE);
    // The data differs, so even though the creation timestamps are identical
    // the write is detected as a conflict: the cache is neither overwritten nor
    // deleted.
    $this->cacheBackend->expects($this->never())
      ->method('set');
    $this->cacheBackend->expects($this->never())
      ->method('delete');
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
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

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
    // Simulate having loaded the same data at the start of the request, so the
    // fingerprint matches and the data is merged rather than treated as changed.
    $this->collector->setLoadedData($cache->data);
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
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();

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
   * Tests deleting the cache after a delete.
   */
  public function testUpdateCacheDelete(): void {
    $this->setUpMockCacheBackend();
    $this->setUpMockLockBackend();
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
      ->with($this->cid, $this->callback(function ($value) use (&$allow_invalid): bool {
        return array_shift($allow_invalid) === $value;
      }))
      ->willReturn($cache);

    $this->collector->delete($key);

    // Set up mock objects for the expected calls, first a lock acquire, then
    // a cache delete and finally the lock is released again.
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
    $this->setUpMockCacheBackend();
    $cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

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
    $cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');
    $this->collector->clear();
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(2, $this->collector->getCacheMisses());
  }

  /**
   * Tests a clear of the cache collector using tags.
   */
  public function testUpdateCacheClearTags(): void {
    $this->setUpMockCacheBackend();
    $cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $this->getContainerWithCacheTagsInvalidator($cacheTagsInvalidator);

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
    $cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($tags);
    $this->collector->clear();
    $this->assertEquals($value, $this->collector->get($key));
    $this->assertEquals(2, $this->collector->getCacheMisses());
  }

}
