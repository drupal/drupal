<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Cache\ChainedFastBackendTest.
 */

namespace Drupal\Tests\Core\Cache;

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
  public function testGetDoesntHitConsistentBackend() {
    $consistent_cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $timestamp_cid = ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_foo';
    // Use REQUEST_TIME because that is what we will be comparing against.
    $timestamp_item = (object) array('cid' => $timestamp_cid, 'data' => REQUEST_TIME - 60);
    $consistent_cache->expects($this->once())
      ->method('get')->with($timestamp_cid)
      ->will($this->returnValue($timestamp_item));
    $consistent_cache->expects($this->never())
      ->method('getMultiple');

    $fast_cache = new MemoryBackend('foo');
    $fast_cache->set('foo', 'baz');

    $chained_fast_backend = new ChainedFastBackend(
      $consistent_cache,
      $fast_cache,
      'foo'
    );
    $this->assertEquals('baz', $chained_fast_backend->get('foo')->data);
  }

  /**
   * Tests a fast cache miss gets data from the consistent cache backend.
   */
  public function testFallThroughToConsistentCache() {
    $timestamp_item = (object) array(
      'cid' => ChainedFastBackend::LAST_WRITE_TIMESTAMP_PREFIX . 'cache_foo',
      'data' => time() + 60, // Time travel is easy.
    );
    $cache_item = (object) array(
      'cid' => 'foo',
      'data' => 'baz',
      'created' => time(),
      'expire' => time() + 3600,
      'tags' => ['tag'],
    );

    $consistent_cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $fast_cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    // We should get a call for the timestamp on the consistent backend.
    $consistent_cache->expects($this->once())
      ->method('get')
      ->with($timestamp_item->cid)
      ->will($this->returnValue($timestamp_item));

    // We should get a call for the cache item on the consistent backend.
    $consistent_cache->expects($this->once())
      ->method('getMultiple')
      ->with(array($cache_item->cid))
      ->will($this->returnValue(array($cache_item->cid => $cache_item)));

    // We should get a call for the cache item on the fast backend.
    $fast_cache->expects($this->once())
      ->method('getMultiple')
      ->with(array($cache_item->cid))
      ->will($this->returnValue(array($cache_item->cid => $cache_item)));

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
