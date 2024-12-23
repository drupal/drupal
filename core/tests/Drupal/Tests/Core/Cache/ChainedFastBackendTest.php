<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\ChainedFastBackend;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\ChainedFastBackend
 * @group Cache
 */
class ChainedFastBackendTest extends UnitTestCase {

  /**
   * The consistent cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $consistentCache;

  /**
   * The fast cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $fastCache;

  /**
   * The cache bin.
   *
   * @var string
   */
  protected $bin;

  /**
   * Tests a get() on the fast backend, with no hit on the consistent backend.
   */
  public function testGetDoesNotHitConsistentBackend(): void {
    $consistent_cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $timestamp_cid = ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_foo';
    // Use the request time because that is what we will be comparing against.
    $timestamp_item = (object) ['cid' => $timestamp_cid, 'data' => (int) $_SERVER['REQUEST_TIME'] - 60];
    $consistent_cache->expects($this->once())
      ->method('get')->with($timestamp_cid)
      ->willReturn($timestamp_item);
    $consistent_cache->expects($this->never())
      ->method('getMultiple');

    $fast_cache = new MemoryBackend(new Time());
    $fast_cache->set('foo', 'baz');

    $chained_fast_backend = new ChainedFastBackend(
      $consistent_cache,
      $fast_cache,
      'foo'
    );
    $this->assertEquals('baz', $chained_fast_backend->get('foo')->data);
  }

  /**
   * Tests a get() on consistent backend without saving on fast backend.
   */
  public function testSetInvalidDataFastBackend(): void {
    $cid = $this->randomString();
    $item = (object) [
      'cid' => $cid,
      'data' => serialize($this->randomObject()),
      'created' => ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_foo',
      'expire' => Cache::PERMANENT,
      'tags' => [],
      'valid' => FALSE,
    ];

    $consistent_cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    $consistent_cache->expects($this->once())
      ->method('get')
      ->withAnyParameters()
      ->willReturn(FALSE);
    $consistent_cache->expects($this->once())
      ->method('getMultiple')
      ->withAnyParameters()
      ->willReturn([$item]);

    $fast_cache = new MemoryBackend(new Time());

    $chained_fast_backend = new ChainedFastBackend(
      $consistent_cache,
      $fast_cache,
      'foo'
    );

    // Perform a get using the allowing invalid data parameter.
    $this->assertEquals($item, $chained_fast_backend->get($cid, TRUE));

    // Perform a get directly on the fast cache to guarantee the invalid data
    // were not saved there.
    $this->assertEquals(NULL, $fast_cache->get($cid), 'Invalid data was not saved on the fast cache.');
  }

  /**
   * Tests a fast cache miss gets data from the consistent cache backend.
   */
  public function testFallThroughToConsistentCache(): void {
    $timestamp_item = (object) [
      'cid' => ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_foo',
      // Time travel is easy.
      'data' => time() + 60,
    ];
    $cache_item = (object) [
      'cid' => 'foo',
      'data' => 'baz',
      'created' => time(),
      'expire' => time() + 3600,
      'tags' => ['tag'],
    ];

    $consistent_cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $fast_cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    // We should get a call for the timestamp on the consistent backend.
    $consistent_cache->expects($this->once())
      ->method('get')
      ->with($timestamp_item->cid)
      ->willReturn($timestamp_item);

    // We should get a call for the cache item on the consistent backend.
    $consistent_cache->expects($this->once())
      ->method('getMultiple')
      ->with([$cache_item->cid])
      ->willReturn([$cache_item->cid => $cache_item]);

    // We should get a call for the cache item on the fast backend.
    $fast_cache->expects($this->once())
      ->method('getMultiple')
      ->with([$cache_item->cid])
      ->willReturn([$cache_item->cid => $cache_item]);

    // We should get a call to set the cache item on the fast backend.
    $fast_cache->expects($this->once())
      ->method('set')
      ->with($cache_item->cid, $cache_item->data, $cache_item->expire);

    $chained_fast_backend = new ChainedFastBackend(
      $consistent_cache,
      $fast_cache,
      'foo'
    );
    $this->assertEquals('baz', $chained_fast_backend->get('foo')->data);
  }

}
