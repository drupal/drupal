<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\MemoryCache\LruMemoryCache;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\MemoryCache\LruMemoryCache
 * @group Cache
 */
class LruMemoryCacheTest extends UnitTestCase {

  /**
   * Tests getting, setting and deleting items from the LRU memory cache.
   *
   * @covers ::get
   * @covers ::set
   * @covers ::delete
   * @covers ::getMultiple
   */
  public function testGetSetDelete(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cache_data = [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ];
    foreach ($cache_data as $items) {
      $lru_cache->set($items[0], $items[1]);
    }
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);

    $lru_cache->set('cuckoo', 'cuckoo');
    $this->assertCacheData($lru_cache, [
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
    ]);

    // Now bring pigeon to the most recently used spot.
    $lru_cache->get('pigeon');
    $this->assertCacheData($lru_cache, [
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
    ]);

    // Confirm that setting the same item multiple times only uses one slot.
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $lru_cache->set('bigger_cuckoo', 'bigger_cuckoo');
    $this->assertCacheData($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
      ['bigger_cuckoo', 'bigger_cuckoo'],
    ]);

    // Confirm that deleting the same item multiple times only frees up one
    // slot.
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $lru_cache->delete('bigger_cuckoo');
    $this->assertCacheData($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
    ]);
    $lru_cache->set('crow', 'crow');

    $this->assertCacheData($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);

    // Ensure nothing changes on cache miss for ::get().
    $this->assertFalse($lru_cache->get('dodo'));
    $this->assertCacheData($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);

    // Ensure nothing changes on cache miss for ::getMultiple().
    $cids = ['dodo', 'great_auk'];
    $this->assertEmpty($lru_cache->getMultiple($cids));
    $this->assertCacheData($lru_cache, [
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);
    $this->assertSame(['dodo', 'great_auk'], $cids);

    $cids = ['pigeon', 'cuckoo'];
    $lru_cache->getMultiple($cids);
    // @todo This result suggests the order of the arguments in the
    //   \Drupal\Core\Cache\MemoryBackend::getMultiple() array_intersect_key()
    //   should be swapped as this order of the cache items returned should
    //   probably be in the same order as the passed in $cache_data. I.e. cuckoo
    //   should be at the ends of the array and not crow.
    $this->assertCacheData($lru_cache, [
      ['crow', 'crow'],
      ['cuckoo', 'cuckoo'],
      ['pigeon', 'pigeon'],
    ]);
    $this->assertEmpty($cids);
  }

  /**
   * Tests setting items with numeric keys in the LRU memory cache.
   *
   * @covers ::set
   */
  public function testSetNumericKeys(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cache_data = [
      [4, 'sparrow'],
      [10, 'pigeon'],
      [7, 'crow'],
    ];
    foreach ($cache_data as $item) {
      $lru_cache->set($item[0], $item[1]);
    }
    $this->assertCacheData($lru_cache, $cache_data);

    $lru_cache->set(1, 'cuckoo');
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [7, 'crow'],
      [1, 'cuckoo'],
    ]);

    $lru_cache->set(7, 'crow');
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [1, 'cuckoo'],
      [7, 'crow'],
    ]);
  }

  /**
   * Tests setting multiple items in the LRU memory cache.
   *
   * @covers ::setMultiple
   */
  public function testSetMultiple(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $lru_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow'],
      'pigeon' => ['data' => 'pigeon'],
      'crow' => ['data' => 'crow'],
    ]);
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);

    $lru_cache->setMultiple([
      'sparrow' => ['data' => 'sparrow2'],
      'bluejay' => ['data' => 'bluejay'],
    ]);
    $this->assertCacheData($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow2'],
      ['bluejay', 'bluejay'],
    ]);

    $lru_cache->setMultiple([
      3 => ['data' => 'pigeon'],
      2 => ['data' => 'eagle'],
      1 => ['data' => 'wren'],
    ]);
    $this->assertCacheData($lru_cache, [
      [3, 'pigeon'],
      [2, 'eagle'],
      [1, 'wren'],
    ]);

    $lru_cache->setMultiple([
      2 => ['data' => 'eagle2'],
      4 => ['data' => 'cuckoo'],
    ]);
    $this->assertCacheData($lru_cache, [
      [1, 'wren'],
      [2, 'eagle2'],
      [4, 'cuckoo'],
    ]);
  }

  /**
   * Tests invalidation from the LRU memory cache.
   *
   * @covers ::invalidate
   * @covers ::invalidateMultiple
   * @covers ::invalidateTags
   */
  public function testInvalidate(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cache_data = [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ];
    foreach ($cache_data as $items) {
      $lru_cache->set($items[0], $items[1]);
    }
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);
    $lru_cache->invalidate('crow');
    $this->assertCacheData($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
    ]);
    $this->assertFalse($lru_cache->get('crow'));
    // Ensure that getting an invalid cache does not move it to the end of the
    // array.
    $this->assertSame('crow', $lru_cache->get('crow', TRUE)->data);
    $this->assertCacheData($lru_cache, [
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
    ]);
    $lru_cache->set('cuckoo', 'cuckoo', LruMemoryCache::CACHE_PERMANENT, ['cuckoo']);
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['cuckoo', 'cuckoo'],
    ]);
    $lru_cache->invalidateTags(['cuckoo']);
    $this->assertFalse($lru_cache->get('cuckoo'));
    $this->assertSame('cuckoo', $lru_cache->get('cuckoo', TRUE)->data);
    $lru_cache->set('crow', 'crow');
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
    ]);

    $lru_cache->invalidateMultiple(['pigeon', 'crow']);
    $cids = ['pigeon', 'crow'];
    $this->assertEmpty($lru_cache->getMultiple($cids));
    $this->assertSame(['pigeon', 'crow'], $cids);
    $this->assertCount(2, $lru_cache->getMultiple($cids, TRUE));
    $this->assertSame([], $cids);
    $this->assertCacheData($lru_cache, [
      ['pigeon', 'pigeon'],
      ['crow', 'crow'],
      ['sparrow', 'sparrow'],
    ]);
    $lru_cache->set('duck', 'duck');
    $lru_cache->set('chicken', 'chicken');
    $this->assertCacheData($lru_cache, [
      ['sparrow', 'sparrow'],
      ['duck', 'duck'],
      ['chicken', 'chicken'],
    ]);
  }

  /**
   * Tests invalidation with numeric keys from the LRU memory cache.
   *
   * @covers ::invalidate
   * @covers ::invalidateMultiple
   * @covers ::invalidateTags
   */
  public function testInvalidateNumeric(): void {
    $lru_cache = $this->getLruMemoryCache(3);

    $cache_data = [
      [3, 'sparrow'],
      [10, 'pigeon'],
      [5, 'crow'],
    ];
    foreach ($cache_data as $items) {
      $lru_cache->set($items[0], $items[1], tags: ['bird']);
    }
    $this->assertCacheData($lru_cache, [
      [3, 'sparrow'],
      [10, 'pigeon'],
      [5, 'crow'],
    ]);

    // Invalidate something not in the cache and ensure nothing changes.
    $lru_cache->invalidate(0);
    $this->assertCacheData($lru_cache, [
      [3, 'sparrow'],
      [10, 'pigeon'],
      [5, 'crow'],
    ]);

    $lru_cache->invalidate(10);
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [3, 'sparrow'],
      [5, 'crow'],
    ]);
    $this->assertFalse($lru_cache->get(10));
    $this->assertSame('pigeon', $lru_cache->get(10, TRUE)->data);

    $lru_cache->invalidateTags(['mammal']);
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [3, 'sparrow'],
      [5, 'crow'],
    ]);
    $this->assertSame('sparrow', $lru_cache->get(3)->data);
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [5, 'crow'],
      [3, 'sparrow'],
    ]);

    $lru_cache->invalidateTags(['mammal', 'bird']);
    $this->assertFalse($lru_cache->get(3));
    $this->assertFalse($lru_cache->get(10));
    $this->assertFalse($lru_cache->get(5));
    $this->assertCacheData($lru_cache, [
      [10, 'pigeon'],
      [5, 'crow'],
      [3, 'sparrow'],
    ]);
  }

  /**
   * Asserts that the given cache data matches the data in the memory cache.
   *
   * @param \Drupal\Core\Cache\MemoryCache\LruMemoryCache $lru_cache
   *   The LRU cache under test.
   * @param array $cache_data
   *   Array whose first element is the cache ID and whose second element is
   *   the value to check. This should contain all the keys in the cache and in
   *   the expected order.
   */
  protected function assertCacheData(LruMemoryCache $lru_cache, array $cache_data): void {
    // Use reflection to access data because using ::get() affects the LRU
    // cache.
    $reflectedClass = new \ReflectionClass($lru_cache);
    $reflection = $reflectedClass->getProperty('cache');
    $cache = $reflection->getValue($lru_cache);

    $keys = [];
    foreach ($cache_data as $item) {
      $keys[] = $item[0];
      $this->assertSame($item[1], $cache[$item[0]]->data, "$item[0] found in cache.");
    }

    // Ensure the cache only contains the supply keys and the order is as
    // expected.
    $this->assertSame($keys, array_keys($cache));
  }

  /**
   * Creates a LRU cache for testing.
   *
   * @param int $slots
   *   The number of slots in the LRU cache.
   *
   * @return \Drupal\Core\Cache\MemoryCache\LruMemoryCache
   *   The LRU cache.
   */
  private function getLruMemoryCache(int $slots): LruMemoryCache {
    $time_mock = $this->createMock(TimeInterface::class);
    $time_mock->expects($this->any())
      ->method('getRequestTime')
      ->willReturnCallback('time');
    return new LruMemoryCache(
      $time_mock,
      $slots,
    );
  }

}
