<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\GetMultipleTest.
 */

namespace Drupal\system\Tests\Cache;

/**
 * Tests getMultiple().
 */
class GetMultipleTest extends CacheTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Fetching multiple cache items',
      'description' => 'Confirm that multiple records are fetched correctly.',
      'group' => 'Cache',
    );
  }

  function setUp() {
    $this->default_bin = 'page';
    parent::setUp();
  }

  /**
   * Test getMultiple().
   */
  function testCacheMultiple() {
    $item1 = $this->randomName(10);
    $item2 = $this->randomName(10);
    $cache = cache($this->default_bin);
    $cache->set('item1', $item1);
    $cache->set('item2', $item2);
    $this->assertTrue($this->checkCacheExists('item1', $item1), t('Item 1 is cached.'));
    $this->assertTrue($this->checkCacheExists('item2', $item2), t('Item 2 is cached.'));

    // Fetch both records from the database with getMultiple().
    $item_ids = array('item1', 'item2');
    $items = $cache->getMultiple($item_ids);
    $this->assertEqual($items['item1']->data, $item1, t('Item was returned from cache successfully.'));
    $this->assertEqual($items['item2']->data, $item2, t('Item was returned from cache successfully.'));

    // Remove one item from the cache.
    $cache->delete('item2');

    // Confirm that only one item is returned by getMultiple().
    $item_ids = array('item1', 'item2');
    $items = $cache->getMultiple($item_ids);
    $this->assertEqual($items['item1']->data, $item1, t('Item was returned from cache successfully.'));
    $this->assertFalse(isset($items['item2']), t('Item was not returned from the cache.'));
    $this->assertTrue(count($items) == 1, t('Only valid cache entries returned.'));
  }
}
