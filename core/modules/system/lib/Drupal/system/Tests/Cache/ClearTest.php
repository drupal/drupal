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
}
