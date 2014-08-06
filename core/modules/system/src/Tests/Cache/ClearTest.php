<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\ClearTest.
 */

namespace Drupal\system\Tests\Cache;

/**
 * Tests our clearing is done the proper way.
 *
 * @group Cache
 */
use Drupal\Core\Cache\Cache;

class ClearTest extends CacheTestBase {

  function setUp() {
    $this->default_bin = 'render';
    $this->default_value = $this->randomMachineName(10);

    parent::setUp();
  }

  /**
   * Tests drupal_flush_all_caches().
   */
  function testFlushAllCaches() {
    // Create cache entries for each flushed cache bin.
    $bins = Cache::getBins();
    $this->assertTrue($bins, 'Cache::getBins() returned bins to flush.');
    foreach ($bins as $bin => $cache_backend) {
      $cid = 'test_cid_clear' . $bin;
      $cache_backend->set($cid, $this->default_value);
    }

    // Remove all caches then make sure that they are cleared.
    drupal_flush_all_caches();

    foreach ($bins as $bin => $cache_backend) {
      $cid = 'test_cid_clear' . $bin;
      $this->assertFalse($this->checkCacheExists($cid, $this->default_value, $bin), format_string('All cache entries removed from @bin.', array('@bin' => $bin)));
    }
  }
}
