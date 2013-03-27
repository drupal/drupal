<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\ClearTest.
 */

namespace Drupal\system\Tests\Cache;

/**
 * Tests cache clearing methods.
 */
use Drupal\Core\Cache\Cache;

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
   * Tests drupal_flush_all_caches().
   */
  function testFlushAllCaches() {
    // Create cache entries for each flushed cache bin.
    $bins = Cache::getBins();
    $this->assertTrue($bins, 'cache_get_bins() returned bins to flush.');
    $bins['menu'] = $this->container->get('cache.menu');
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
