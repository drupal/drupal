<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\ClearTest.
 */

namespace Drupal\system\Tests\Cache;

/**
 * Tests cache clearing methods.
 */
class ClearTest extends CacheTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Cache clear test',
      'description' => 'Check our clearing is done the proper way.',
      'group' => 'Cache'
    );
  }

  function setUp() {
    $this->default_bin = 'page';
    $this->default_value = $this->randomName(10);

    parent::setUp();
  }

  /**
   * Test clearing using a cid.
   */
  function testClearCid() {
    $cache = cache($this->default_bin);
    $cache->set('test_cid_clear', $this->default_value);

    $this->assertCacheExists(t('Cache was set for clearing cid.'), $this->default_value, 'test_cid_clear');
    $cache->delete('test_cid_clear');

    $this->assertCacheRemoved(t('Cache was removed after clearing cid.'), 'test_cid_clear');
  }

  /**
   * Test clearing using wildcard.
   */
  function testClearWildcard() {
    $cache = cache($this->default_bin);
    $cache->set('test_cid_clear1', $this->default_value);
    $cache->set('test_cid_clear2', $this->default_value);
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two caches were created for checking cid "*" with wildcard true.'));
    $cache->flush();
    $this->assertFalse($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      || $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two caches removed after clearing cid "*" with wildcard true.'));

    $cache->set('test_cid_clear1', $this->default_value);
    $cache->set('test_cid_clear2', $this->default_value);
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two caches were created for checking cid substring with wildcard true.'));
    $cache->deletePrefix('test_');
    $this->assertFalse($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      || $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two caches removed after clearing cid substring with wildcard true.'));
  }

  /**
   * Test clearing using an array.
   */
  function testClearArray() {
    // Create three cache entries.
    $cache = cache($this->default_bin);
    $cache->set('test_cid_clear1', $this->default_value);
    $cache->set('test_cid_clear2', $this->default_value);
    $cache->set('test_cid_clear3', $this->default_value);
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear3', $this->default_value),
                      t('Three cache entries were created.'));

    // Clear two entries using an array.
    $cache->deleteMultiple(array('test_cid_clear1', 'test_cid_clear2'));
    $this->assertFalse($this->checkCacheExists('test_cid_clear1', $this->default_value)
                       || $this->checkCacheExists('test_cid_clear2', $this->default_value),
                       t('Two cache entries removed after clearing with an array.'));

    $this->assertTrue($this->checkCacheExists('test_cid_clear3', $this->default_value),
                      t('Entry was not cleared from the cache'));

    // Set the cache clear threshold to 2 to confirm that the full bin is cleared
    // when the threshold is exceeded.
    variable_set('cache_clear_threshold', 2);
    $cache->set('test_cid_clear1', $this->default_value);
    $cache->set('test_cid_clear2', $this->default_value);
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two cache entries were created.'));
    $cache->deleteMultiple(array('test_cid_clear1', 'test_cid_clear2', 'test_cid_clear3'));
    $this->assertFalse($this->checkCacheExists('test_cid_clear1', $this->default_value)
                       || $this->checkCacheExists('test_cid_clear2', $this->default_value)
                       || $this->checkCacheExists('test_cid_clear3', $this->default_value),
                       t('All cache entries removed when the array exceeded the cache clear threshold.'));
  }

  /**
   * Test drupal_flush_all_caches().
   */
  function testFlushAllCaches() {
    // Create cache entries for each flushed cache bin.
    $bins = module_invoke_all('cache_flush');
    $this->assertTrue($bins, 'hook_cache_flush() returned bins to flush.');
    $bins = array_merge($bins, array('menu'));
    foreach ($bins as $id => $bin) {
      $cid = 'test_cid_clear' . $id;
      cache($bin)->set($cid, $this->default_value);
    }

    // Remove all caches then make sure that they are cleared.
    drupal_flush_all_caches();

    foreach ($bins as $id => $bin) {
      $cid = 'test_cid_clear' . $id;
      $this->assertFalse($this->checkCacheExists($cid, $this->default_value, $bin), t('All cache entries removed from @bin.', array('@bin' => $bin)));
    }
  }

  /**
   * Test clearing using cache tags.
   */
  function testClearTags() {
    $cache = cache($this->default_bin);
    $cache->set('test_cid_clear1', $this->default_value, CACHE_PERMANENT, array('test_tag' => array(1)));
    $cache->set('test_cid_clear2', $this->default_value, CACHE_PERMANENT, array('test_tag' => array(1)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two cache items were created.'));
    cache_invalidate(array('test_tag' => array(1)));
    $this->assertFalse($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      || $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Two caches removed after clearing a cache tag.'));

    $cache->set('test_cid_clear1', $this->default_value, CACHE_PERMANENT, array('test_tag' => array(1)));
    $cache->set('test_cid_clear2', $this->default_value, CACHE_PERMANENT, array('test_tag' => array(2)));
    $cache->set('test_cid_clear3', $this->default_value, CACHE_PERMANENT, array('test_tag_foo' => array(3)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear3', $this->default_value),
                      t('Two cached items were created.'));
    cache_invalidate(array('test_tag_foo' => array(3)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value)
                      && $this->checkCacheExists('test_cid_clear2', $this->default_value),
                      t('Cached items not matching the tag were not cleared.'));

    $this->assertFalse($this->checkCacheExists('test_cid_clear3', $this->default_value),
                      t('Cached item matching the tag was removed.'));

    // For our next trick, we will attempt to clear data in multiple bins.
    $tags = array('test_tag' => array(1, 2, 3));
    $bins = array('cache', 'cache_page', 'cache_bootstrap');
    foreach ($bins as $bin) {
      cache($bin)->set('test', $this->default_value, CACHE_PERMANENT, $tags);
      $this->assertTrue($this->checkCacheExists('test', $this->default_value, $bin), 'Cache item was set in bin.');
    }
    cache_invalidate(array('test_tag' => array(2)));
    foreach ($bins as $bin) {
      $this->assertFalse($this->checkCacheExists('test', $this->default_value, $bin), 'Tag expire affected item in bin.');
    }
    $this->assertFalse($this->checkCacheExists('test_cid_clear2', $this->default_value), 'Cached items matching tag were cleared.');
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $this->default_value), 'Cached items not matching tag were not cleared.');
  }
}
