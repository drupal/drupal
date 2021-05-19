<?php

namespace Drupal\KernelTests\Core\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests any cache backend.
 *
 * Full generic unit test suite for any cache backend. In order to use it for a
 * cache backend implementation, extend this class and override the
 * createBackendInstance() method to return an object.
 *
 * @see DatabaseBackendUnitTestCase
 *   For a full working implementation.
 */
abstract class GenericCacheBackendUnitTestBase extends KernelTestBase {

  /**
   * Array of objects implementing Drupal\Core\Cache\CacheBackendInterface.
   *
   * @var array
   */
  protected $cachebackends;

  /**
   * Cache bin to use for testing.
   *
   * @var string
   */
  protected $testBin;

  /**
   * Random value to use in tests.
   *
   * @var string
   */
  protected $defaultValue;

  /**
   * Gets the testing bin.
   *
   * Override this method if you want to work on a different bin than the
   * default one.
   *
   * @return string
   *   Bin name.
   */
  protected function getTestBin() {
    if (!isset($this->testBin)) {
      $this->testBin = 'page';
    }
    return $this->testBin;
  }

  /**
   * Creates a cache backend to test.
   *
   * Override this method to test a CacheBackend.
   *
   * @param string $bin
   *   Bin name to use for this backend instance.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   Cache backend to test.
   */
  abstract protected function createCacheBackend($bin);

  /**
   * Allows specific implementation to change the environment before a test run.
   */
  public function setUpCacheBackend() {
  }

  /**
   * Allows alteration of environment after a test run but before tear down.
   *
   * Used before the real tear down because the tear down will change things
   * such as the database prefix.
   */
  public function tearDownCacheBackend() {
  }

  /**
   * Gets a backend to test; this will get a shared instance set in the object.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   Cache backend to test.
   */
  protected function getCacheBackend($bin = NULL) {
    if (!isset($bin)) {
      $bin = $this->getTestBin();
    }
    if (!isset($this->cachebackends[$bin])) {
      $this->cachebackends[$bin] = $this->createCacheBackend($bin);
      // Ensure the backend is empty.
      $this->cachebackends[$bin]->deleteAll();
    }
    return $this->cachebackends[$bin];
  }

  protected function setUp() {
    $this->cachebackends = [];
    $this->defaultValue = $this->randomMachineName(10);

    parent::setUp();

    $this->setUpCacheBackend();
  }

  protected function tearDown() {
    // Destruct the registered backend, each test will get a fresh instance,
    // properly emptying it here ensure that on persistent data backends they
    // will come up empty the next test.
    foreach ($this->cachebackends as $bin => $cachebackend) {
      $this->cachebackends[$bin]->deleteAll();
    }
    unset($this->cachebackends);

    $this->tearDownCacheBackend();

    parent::tearDown();
  }

  /**
   * Tests the get and set methods of Drupal\Core\Cache\CacheBackendInterface.
   */
  public function testSetGet() {
    $backend = $this->getCacheBackend();

    $this->assertFalse($backend->get('test1'), "Backend does not contain data for cache id test1.");
    $with_backslash = ['foo' => '\Drupal\foo\Bar'];
    $backend->set('test1', $with_backslash);
    $cached = $backend->get('test1');
    $this->assertIsObject($cached);
    $this->assertSame($with_backslash, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    // We need to round because microtime may be rounded up in the backend.
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached->created);
    $this->assertEquals(Cache::PERMANENT, $cached->expire, 'Expire time is correct.');

    $this->assertFalse($backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', ['value' => 3], REQUEST_TIME + 3);
    $cached = $backend->get('test2');
    $this->assertIsObject($cached);
    $this->assertSame(['value' => 3], $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached->created);
    $this->assertEquals(REQUEST_TIME + 3, $cached->expire, 'Expire time is correct.');

    $backend->set('test3', 'foobar', REQUEST_TIME - 3);
    $this->assertFalse($backend->get('test3'), 'Invalid item not returned.');
    $cached = $backend->get('test3', TRUE);
    $this->assertIsObject($cached);
    $this->assertFalse($cached->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached->created);
    $this->assertEquals(REQUEST_TIME - 3, $cached->expire, 'Expire time is correct.');

    $this->assertFalse($backend->get('test4'), "Backend does not contain data for cache id test4.");
    $with_eof = ['foo' => "\nEOF\ndata"];
    $backend->set('test4', $with_eof);
    $cached = $backend->get('test4');
    $this->assertIsObject($cached);
    $this->assertSame($with_eof, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached->created);
    $this->assertEquals(Cache::PERMANENT, $cached->expire, 'Expire time is correct.');

    $this->assertFalse($backend->get('test5'), "Backend does not contain data for cache id test5.");
    $with_eof_and_semicolon = ['foo' => "\nEOF;\ndata"];
    $backend->set('test5', $with_eof_and_semicolon);
    $cached = $backend->get('test5');
    $this->assertIsObject($cached);
    $this->assertSame($with_eof_and_semicolon, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached->created);
    $this->assertEquals(Cache::PERMANENT, $cached->expire, 'Expire time is correct.');

    $with_variable = ['foo' => '$bar'];
    $backend->set('test6', $with_variable);
    $cached = $backend->get('test6');
    $this->assertIsObject($cached);
    $this->assertSame($with_variable, $cached->data);

    // Make sure that a cached object is not affected by changing the original.
    $data = new \stdClass();
    $data->value = 1;
    $data->obj = new \stdClass();
    $data->obj->value = 2;
    $backend->set('test7', $data);
    $expected_data = clone $data;
    // Add a property to the original. It should not appear in the cached data.
    $data->this_should_not_be_in_the_cache = TRUE;
    $cached = $backend->get('test7');
    $this->assertIsObject($cached);
    $this->assertEquals($expected_data, $cached->data);
    $this->assertFalse(isset($cached->data->this_should_not_be_in_the_cache));
    // Add a property to the cache data. It should not appear when we fetch
    // the data from cache again.
    $cached->data->this_should_not_be_in_the_cache = TRUE;
    $fresh_cached = $backend->get('test7');
    $this->assertFalse(isset($fresh_cached->data->this_should_not_be_in_the_cache));

    // Check with a long key.
    $cid = str_repeat('a', 300);
    $backend->set($cid, 'test');
    $this->assertEquals('test', $backend->get($cid)->data);

    // Check that the cache key is case sensitive.
    $backend->set('TEST8', 'value');
    $this->assertEquals('value', $backend->get('TEST8')->data);
    $this->assertFalse($backend->get('test8'));

    // Calling ::set() with invalid cache tags. This should fail an assertion.
    try {
      $backend->set('assertion_test', 'value', Cache::PERMANENT, ['node' => [3, 5, 7]]);
      $this->fail('::set() was called with invalid cache tags, but runtime assertion did not fail.');
    }
    catch (\AssertionError $e) {
      // Do nothing; continue testing in extending classes.
    }
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function testDelete() {
    $backend = $this->getCacheBackend();

    $this->assertFalse($backend->get('test1'), "Backend does not contain data for cache id test1.");
    $backend->set('test1', 7);
    $this->assertIsObject($backend->get('test1'));

    $this->assertFalse($backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', 3);
    $this->assertIsObject($backend->get('test2'));

    $backend->delete('test1');
    $this->assertFalse($backend->get('test1'), "Backend does not contain data for cache id test1 after deletion.");

    $this->assertIsObject($backend->get('test2'));

    $backend->delete('test2');
    $this->assertFalse($backend->get('test2'), "Backend does not contain data for cache id test2 after deletion.");

    $long_cid = str_repeat('a', 300);
    $backend->set($long_cid, 'test');
    $backend->delete($long_cid);
    $this->assertFalse($backend->get($long_cid), "Backend does not contain data for long cache id after deletion.");
  }

  /**
   * Tests data type preservation.
   */
  public function testValueTypeIsKept() {
    $backend = $this->getCacheBackend();

    $variables = [
      'test1' => 1,
      'test2' => '0',
      'test3' => '',
      'test4' => 12.64,
      'test5' => FALSE,
      'test6' => [1, 2, 3],
    ];

    // Create cache entries.
    foreach ($variables as $cid => $data) {
      $backend->set($cid, $data);
    }

    // Retrieve and test cache objects.
    foreach ($variables as $cid => $value) {
      $object = $backend->get($cid);
      $this->assertIsObject($object, sprintf("Backend returned an object for cache id %s.", $cid));
      $this->assertSame($value, $object->data, sprintf("Data of cached id %s kept is identical in type and value", $cid));
    }
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function testGetMultiple() {
    $backend = $this->getCacheBackend();

    // Set numerous testing keys.
    $long_cid = str_repeat('a', 300);
    $backend->set('test1', 1);
    $backend->set('test2', 3);
    $backend->set('test3', 5);
    $backend->set('test4', 7);
    $backend->set('test5', 11);
    $backend->set('test6', 13);
    $backend->set('test7', 17);
    $backend->set($long_cid, 300);

    // Mismatch order for harder testing.
    $reference = [
      'test3',
      'test7',
      // Cid does not exist.
      'test21',
      'test6',
      // Cid does not exist until added before second getMultiple().
      'test19',
      'test2',
    ];

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    // Test return - ensure it contains existing cache ids.
    $this->assertArrayHasKey('test2', $ret, "Existing cache id test2 is set.");
    $this->assertArrayHasKey('test3', $ret, "Existing cache id test3 is set.");
    $this->assertArrayHasKey('test6', $ret, "Existing cache id test6 is set.");
    $this->assertArrayHasKey('test7', $ret, "Existing cache id test7 is set.");
    // Test return - ensure that objects has expected properties.
    $this->assertTrue($ret['test2']->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $ret['test2']->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $ret['test2']->created);
    $this->assertEquals(Cache::PERMANENT, $ret['test2']->expire, 'Expire time is correct.');
    // Test return - ensure it does not contain nonexistent cache ids.
    $this->assertFalse(isset($ret['test19']), "Nonexistent cache id test19 is not set.");
    $this->assertFalse(isset($ret['test21']), "Nonexistent cache id test21 is not set.");
    // Test values.
    $this->assertSame(3, $ret['test2']->data, "Existing cache id test2 has the correct value.");
    $this->assertSame(5, $ret['test3']->data, "Existing cache id test3 has the correct value.");
    $this->assertSame(13, $ret['test6']->data, "Existing cache id test6 has the correct value.");
    $this->assertSame(17, $ret['test7']->data, "Existing cache id test7 has the correct value.");
    // Test $cids array - ensure it contains cache id's that do not exist.
    $this->assertContains('test19', $cids, "Nonexistent cache id test19 is in cids array.");
    $this->assertContains('test21', $cids, "Nonexistent cache id test21 is in cids array.");
    // Test $cids array - ensure it does not contain cache id's that exist.
    $this->assertNotContains('test2', $cids, "Existing cache id test2 is not in cids array.");
    $this->assertNotContains('test3', $cids, "Existing cache id test3 is not in cids array.");
    $this->assertNotContains('test6', $cids, "Existing cache id test6 is not in cids array.");
    $this->assertNotContains('test7', $cids, "Existing cache id test7 is not in cids array.");

    // Test a second time after deleting and setting new keys which ensures that
    // if the backend uses statics it does not cause unexpected results.
    $backend->delete('test3');
    $backend->delete('test6');
    $backend->set('test19', 57);

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    // Test return - ensure it contains existing cache ids.
    $this->assertArrayHasKey('test2', $ret, "Existing cache id test2 is set");
    $this->assertArrayHasKey('test7', $ret, "Existing cache id test7 is set");
    $this->assertArrayHasKey('test19', $ret, "Added cache id test19 is set");
    // Test return - ensure it does not contain nonexistent cache ids.
    $this->assertFalse(isset($ret['test3']), "Deleted cache id test3 is not set");
    $this->assertFalse(isset($ret['test6']), "Deleted cache id test6 is not set");
    $this->assertFalse(isset($ret['test21']), "Nonexistent cache id test21 is not set");
    // Test values.
    $this->assertSame(3, $ret['test2']->data, "Existing cache id test2 has the correct value.");
    $this->assertSame(17, $ret['test7']->data, "Existing cache id test7 has the correct value.");
    $this->assertSame(57, $ret['test19']->data, "Added cache id test19 has the correct value.");
    // Test $cids array - ensure it contains cache id's that do not exist.
    $this->assertContains('test3', $cids, "Deleted cache id test3 is in cids array.");
    $this->assertContains('test6', $cids, "Deleted cache id test6 is in cids array.");
    $this->assertContains('test21', $cids, "Nonexistent cache id test21 is in cids array.");
    // Test $cids array - ensure it does not contain cache id's that exist.
    $this->assertNotContains('test2', $cids, "Existing cache id test2 is not in cids array.");
    $this->assertNotContains('test7', $cids, "Existing cache id test7 is not in cids array.");
    $this->assertNotContains('test19', $cids, "Added cache id test19 is not in cids array.");

    // Test with a long $cid and non-numeric array key.
    $cids = ['key:key' => $long_cid];
    $return = $backend->getMultiple($cids);
    $this->assertEquals(300, $return[$long_cid]->data);
    $this->assertTrue(empty($cids));
  }

  /**
   * Tests \Drupal\Core\Cache\CacheBackendInterface::setMultiple().
   */
  public function testSetMultiple() {
    $backend = $this->getCacheBackend();

    $future_expiration = REQUEST_TIME + 100;

    // Set multiple testing keys.
    $backend->set('cid_1', 'Some other value');
    $items = [
      'cid_1' => ['data' => 1],
      'cid_2' => ['data' => 2],
      'cid_3' => ['data' => [1, 2]],
      'cid_4' => ['data' => 1, 'expire' => $future_expiration],
      'cid_5' => ['data' => 1, 'tags' => ['test:a', 'test:b']],
    ];
    $backend->setMultiple($items);
    $cids = array_keys($items);
    $cached = $backend->getMultiple($cids);

    $this->assertEquals($items['cid_1']['data'], $cached['cid_1']->data, 'Over-written cache item set correctly.');
    $this->assertTrue($cached['cid_1']->valid, 'Item is marked as valid.');
    $this->assertGreaterThanOrEqual(REQUEST_TIME, $cached['cid_1']->created);
    $this->assertLessThanOrEqual(round(microtime(TRUE), 3), $cached['cid_1']->created);
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $cached['cid_1']->expire, 'Cache expiration defaults to permanent.');

    $this->assertEquals($items['cid_2']['data'], $cached['cid_2']->data, 'New cache item set correctly.');
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $cached['cid_2']->expire, 'Cache expiration defaults to permanent.');

    $this->assertEquals($items['cid_3']['data'], $cached['cid_3']->data, 'New cache item with serialized data set correctly.');
    $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $cached['cid_3']->expire, 'Cache expiration defaults to permanent.');

    $this->assertEquals($items['cid_4']['data'], $cached['cid_4']->data, 'New cache item set correctly.');
    $this->assertEquals($future_expiration, $cached['cid_4']->expire, 'Cache expiration has been correctly set.');

    $this->assertEquals($items['cid_5']['data'], $cached['cid_5']->data, 'New cache item set correctly.');

    // Calling ::setMultiple() with invalid cache tags. This should fail an
    // assertion.
    try {
      $items = [
        'exception_test_1' => ['data' => 1, 'tags' => []],
        'exception_test_2' => ['data' => 2, 'tags' => ['valid']],
        'exception_test_3' => ['data' => 3, 'tags' => ['node' => [3, 5, 7]]],
      ];
      $backend->setMultiple($items);
      $this->fail('::setMultiple() was called with invalid cache tags, but runtime assertion did not fail.');
    }
    catch (\AssertionError $e) {
      // Do nothing; continue testing in extending classes.
    }
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::delete() and
   * Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
   */
  public function testDeleteMultiple() {
    $backend = $this->getCacheBackend();

    // Set numerous testing keys.
    $backend->set('test1', 1);
    $backend->set('test2', 3);
    $backend->set('test3', 5);
    $backend->set('test4', 7);
    $backend->set('test5', 11);
    $backend->set('test6', 13);
    $backend->set('test7', 17);

    $backend->delete('test1');
    // Nonexistent key should not cause an error.
    $backend->delete('test23');
    $backend->deleteMultiple([
      'test3',
      'test5',
      'test7',
      // Nonexistent key should not cause an error.
      'test19',
      // Nonexistent key should not cause an error.
      'test21',
    ]);

    // Test if expected keys have been deleted.
    $this->assertFalse($backend->get('test1'), "Cache id test1 deleted.");
    $this->assertFalse($backend->get('test3'), "Cache id test3 deleted.");
    $this->assertFalse($backend->get('test5'), "Cache id test5 deleted.");
    $this->assertFalse($backend->get('test7'), "Cache id test7 deleted.");

    // Test if expected keys exist.
    $this->assertNotFalse($backend->get('test2'), "Cache id test2 exists.");
    $this->assertNotFalse($backend->get('test4'), "Cache id test4 exists.");
    $this->assertNotFalse($backend->get('test6'), "Cache id test6 exists.");

    // Test if that expected keys do not exist.
    $this->assertFalse($backend->get('test19'), "Cache id test19 does not exist.");
    $this->assertFalse($backend->get('test21'), "Cache id test21 does not exist.");

    // Calling deleteMultiple() with an empty array should not cause an error.
    $this->assertNull($backend->deleteMultiple([]));
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function testDeleteAll() {
    $backend_a = $this->getCacheBackend();
    $backend_b = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend_a->set('test1', 1, Cache::PERMANENT);
    $backend_a->set('test2', 3, time() + 1000);
    $backend_b->set('test3', 4, Cache::PERMANENT);

    $backend_a->deleteAll();

    $this->assertFalse($backend_a->get('test1'), 'First key has been deleted.');
    $this->assertFalse($backend_a->get('test2'), 'Second key has been deleted.');
    $this->assertNotEmpty($backend_b->get('test3'), 'Item in other bin is preserved.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidate() and
   * Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  public function testInvalidate() {
    $backend = $this->getCacheBackend();
    $backend->set('test1', 1);
    $backend->set('test2', 2);
    $backend->set('test3', 2);
    $backend->set('test4', 2);

    $reference = ['test1', 'test2', 'test3', 'test4'];

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertCount(4, $ret, 'Four items returned.');

    $backend->invalidate('test1');
    $backend->invalidateMultiple(['test2', 'test3']);

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertCount(1, $ret, 'Only one item element returned.');

    $cids = $reference;
    $ret = $backend->getMultiple($cids, TRUE);
    $this->assertCount(4, $ret, 'Four items returned.');

    // Calling invalidateMultiple() with an empty array should not cause an
    // error.
    $this->assertNull($backend->invalidateMultiple([]));
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  public function testInvalidateTags() {
    $backend = $this->getCacheBackend();

    // Create two cache entries with the same tag and tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, ['test_tag:2']);
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, ['test_tag:2']);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1')->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2')->data);

    // Invalidate test_tag of value 1. This should invalidate both entries.
    Cache::invalidateTags(['test_tag:2']);
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two cache items invalidated after invalidating a cache tag.');
    // Verify that cache items have not been deleted after invalidation.
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1', TRUE)->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2', TRUE)->data);

    // Create two cache entries with the same tag and an array tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, ['test_tag:1']);
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, ['test_tag:1']);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1')->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2')->data);

    // Invalidate test_tag of value 1. This should invalidate both entries.
    Cache::invalidateTags(['test_tag:1']);
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two caches removed after invalidating a cache tag.');
    // Verify that cache items have not been deleted after invalidation.
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1', TRUE)->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2', TRUE)->data);

    // Create three cache entries with a mix of tags and tag values.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, ['test_tag:1']);
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, ['test_tag:2']);
    $backend->set('test_cid_invalidate3', $this->defaultValue, Cache::PERMANENT, ['test_tag_foo:3']);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1')->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2')->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate3')->data);
    Cache::invalidateTags(['test_tag_foo:3']);
    // Verify that cache items not matching the tag were not invalidated.
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate1')->data);
    $this->assertSame($this->defaultValue, $backend->get('test_cid_invalidate2')->data);
    $this->assertFalse($backend->get('test_cid_invalidate3'), 'Cached item matching the tag was removed.');

    // Create cache entry in multiple bins. Two cache entries
    // (test_cid_invalidate1 and test_cid_invalidate2) still exist from previous
    // tests.
    $tags = ['test_tag:1', 'test_tag:2', 'test_tag:3'];
    $bins = ['path', 'bootstrap', 'page'];
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->set('test', $this->defaultValue, Cache::PERMANENT, $tags);
      $this->assertNotEmpty($this->getCacheBackend($bin)->get('test'), 'Cache item was set in bin.');
    }

    Cache::invalidateTags(['test_tag:2']);

    // Test that the cache entry has been invalidated in multiple bins.
    foreach ($bins as $bin) {
      $this->assertFalse($this->getCacheBackend($bin)->get('test'), 'Tag invalidation affected item in bin.');
    }
    // Test that the cache entry with a matching tag has been invalidated.
    $this->assertFalse($this->getCacheBackend($bin)->get('test_cid_invalidate2'), 'Cache items matching tag were invalidated.');
    // Test that the cache entry with without a matching tag still exists.
    $this->assertNotEmpty($this->getCacheBackend($bin)->get('test_cid_invalidate1'), 'Cache items not matching tag were not invalidated.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function testInvalidateAll() {
    $backend_a = $this->getCacheBackend();
    $backend_b = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend_a->set('test1', 1, Cache::PERMANENT);
    $backend_a->set('test2', 3, time() + 1000);
    $backend_b->set('test3', 4, Cache::PERMANENT);

    $backend_a->invalidateAll();

    $this->assertFalse($backend_a->get('test1'), 'First key has been invalidated.');
    $this->assertFalse($backend_a->get('test2'), 'Second key has been invalidated.');
    $this->assertNotEmpty($backend_b->get('test3'), 'Item in other bin is preserved.');
    $this->assertNotEmpty($backend_a->get('test1', TRUE), 'First key has not been deleted.');
    $this->assertNotEmpty($backend_a->get('test2', TRUE), 'Second key has not been deleted.');
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::removeBin().
   */
  public function testRemoveBin() {
    $backend_a = $this->getCacheBackend();
    $backend_b = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend_a->set('test1', 1, Cache::PERMANENT);
    $backend_a->set('test2', 3, time() + 1000);
    $backend_b->set('test3', 4, Cache::PERMANENT);

    $backend_a->removeBin();

    $this->assertFalse($backend_a->get('test1'), 'First key has been deleted.');
    $this->assertFalse($backend_a->get('test2', TRUE), 'Second key has been deleted.');
    $this->assertNotEmpty($backend_b->get('test3'), 'Item in other bin is preserved.');
  }

}
