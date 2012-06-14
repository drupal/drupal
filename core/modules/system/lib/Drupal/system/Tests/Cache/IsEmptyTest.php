<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\IsEmptyTest.
 */

namespace Drupal\system\Tests\Cache;

/**
 * Tests the isEmpty() method.
 */
class IsEmptyTest extends CacheTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Cache emptiness test',
      'description' => 'Check if a cache bin is empty after performing clear operations.',
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
  function testIsEmpty() {
    // Clear the cache bin.
    $cache = cache($this->default_bin);
    $cache->flush();
    $this->assertTrue($cache->isEmpty(), t('The cache bin is empty'));
    // Add some data to the cache bin.
    $cache->set($this->default_cid, $this->default_value);
    $this->assertCacheExists(t('Cache was set.'), $this->default_value, $this->default_cid);
    $this->assertFalse($cache->isEmpty(), t('The cache bin is not empty'));
    // Remove the cached data.
    $cache->delete($this->default_cid);
    $this->assertCacheRemoved(t('Cache was removed.'), $this->default_cid);
    $this->assertTrue($cache->isEmpty(), t('The cache bin is empty'));
  }
}
