<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Cache\GenericCacheBackendUnitTestBase.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests any cache backend.
 *
 * Full generic unit test suite for any cache backend. In order to use it for a
 * cache backend implementation, extend this class and override the
 * createBackendInstace() method to return an object.
 *
 * @see DatabaseBackendUnitTestCase
 *   For a full working implementation.
 */
abstract class GenericCacheBackendUnitTestBase extends DrupalUnitTestBase {

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
  protected abstract function createCacheBackend($bin);

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
  protected function getCacheBackend($bin = null) {
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
    $this->cachebackends = array();
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

    $this->assertIdentical(FALSE, $backend->get('test1'), "Backend does not contain data for cache id test1.");
    $with_backslash = array('foo' => '\Drupal\foo\Bar');
    $backend->set('test1', $with_backslash);
    $cached = $backend->get('test1');
    $this->assert(is_object($cached), "Backend returned an object for cache id test1.");
    $this->assertIdentical($with_backslash, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    // We need to round because microtime may be rounded up in the backend.
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', array('value' => 3), REQUEST_TIME + 3);
    $cached = $backend->get('test2');
    $this->assert(is_object($cached), "Backend returned an object for cache id test2.");
    $this->assertIdentical(array('value' => 3), $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, REQUEST_TIME + 3, 'Expire time is correct.');

    $backend->set('test3', 'foobar', REQUEST_TIME - 3);
    $this->assertFalse($backend->get('test3'), 'Invalid item not returned.');
    $cached = $backend->get('test3', TRUE);
    $this->assert(is_object($cached), 'Backend returned an object for cache id test3.');
    $this->assertFalse($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, REQUEST_TIME - 3, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test4'), "Backend does not contain data for cache id test4.");
    $with_eof = array('foo' => "\nEOF\ndata");
    $backend->set('test4', $with_eof);
    $cached = $backend->get('test4');
    $this->assert(is_object($cached), "Backend returned an object for cache id test4.");
    $this->assertIdentical($with_eof, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $this->assertIdentical(FALSE, $backend->get('test5'), "Backend does not contain data for cache id test5.");
    $with_eof_and_semicolon = array('foo' => "\nEOF;\ndata");
    $backend->set('test5', $with_eof_and_semicolon);
    $cached = $backend->get('test5');
    $this->assert(is_object($cached), "Backend returned an object for cache id test5.");
    $this->assertIdentical($with_eof_and_semicolon, $cached->data);
    $this->assertTrue($cached->valid, 'Item is marked as valid.');
    $this->assertTrue($cached->created >= REQUEST_TIME && $cached->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached->expire, Cache::PERMANENT, 'Expire time is correct.');

    $with_variable = array('foo' => '$bar');
    $backend->set('test6', $with_variable);
    $cached = $backend->get('test6');
    $this->assert(is_object($cached), "Backend returned an object for cache id test6.");
    $this->assertIdentical($with_variable, $cached->data);

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
    $this->assert(is_object($cached), "Backend returned an object for cache id test7.");
    $this->assertEqual($expected_data, $cached->data);
    $this->assertFalse(isset($cached->data->this_should_not_be_in_the_cache));
    // Add a property to the cache data. It should not appear when we fetch
    // the data from cache again.
    $cached->data->this_should_not_be_in_the_cache = TRUE;
    $fresh_cached = $backend->get('test7');
    $this->assertFalse(isset($fresh_cached->data->this_should_not_be_in_the_cache));

    // Check with a long key.
    $cid = str_repeat('a', 300);
    $backend->set($cid, 'test');
    $this->assertEqual('test', $backend->get($cid)->data);

    // Calling ::set() with invalid cache tags.
    try {
      $backend->set('exception_test', 'value', Cache::PERMANENT, ['node' => [3, 5, 7]]);
      $this->fail('::set() was called with invalid cache tags, no exception was thrown.');
    }
    catch (\LogicException $e) {
      $this->pass('::set() was called with invalid cache tags, an exception was thrown.');
    }
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::delete().
   */
  public function testDelete() {
    $backend = $this->getCacheBackend();

    $this->assertIdentical(FALSE, $backend->get('test1'), "Backend does not contain data for cache id test1.");
    $backend->set('test1', 7);
    $this->assert(is_object($backend->get('test1')), "Backend returned an object for cache id test1.");

    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', 3);
    $this->assert(is_object($backend->get('test2')), "Backend returned an object for cache id %cid.");

    $backend->delete('test1');
    $this->assertIdentical(FALSE, $backend->get('test1'), "Backend does not contain data for cache id test1 after deletion.");

    $this->assert(is_object($backend->get('test2')), "Backend still has an object for cache id test2.");

    $backend->delete('test2');
    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2 after deletion.");

    $long_cid = str_repeat('a', 300);
    $backend->set($long_cid, 'test');
    $backend->delete($long_cid);
    $this->assertIdentical(FALSE, $backend->get($long_cid), "Backend does not contain data for long cache id after deletion.");
  }

  /**
   * Tests data type preservation.
   */
  public function testValueTypeIsKept() {
    $backend = $this->getCacheBackend();

    $variables = array(
      'test1' => 1,
      'test2' => '0',
      'test3' => '',
      'test4' => 12.64,
      'test5' => FALSE,
      'test6' => array(1,2,3),
    );

    // Create cache entries.
    foreach ($variables as $cid => $data) {
      $backend->set($cid, $data);
    }

    // Retrieve and test cache objects.
    foreach ($variables as $cid => $value) {
      $object = $backend->get($cid);
      $this->assert(is_object($object), sprintf("Backend returned an object for cache id %s.", $cid));
      $this->assertIdentical($value, $object->data, sprintf("Data of cached id %s kept is identical in type and value", $cid));
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
    $reference = array(
      'test3',
      'test7',
      'test21', // Cid does not exist.
      'test6',
      'test19', // Cid does not exist until added before second getMultiple().
      'test2',
    );

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    // Test return - ensure it contains existing cache ids.
    $this->assert(isset($ret['test2']), "Existing cache id test2 is set.");
    $this->assert(isset($ret['test3']), "Existing cache id test3 is set.");
    $this->assert(isset($ret['test6']), "Existing cache id test6 is set.");
    $this->assert(isset($ret['test7']), "Existing cache id test7 is set.");
    // Test return - ensure that objects has expected properties.
    $this->assertTrue($ret['test2']->valid, 'Item is marked as valid.');
    $this->assertTrue($ret['test2']->created >= REQUEST_TIME && $ret['test2']->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($ret['test2']->expire, Cache::PERMANENT, 'Expire time is correct.');
    // Test return - ensure it does not contain nonexistent cache ids.
    $this->assertFalse(isset($ret['test19']), "Nonexistent cache id test19 is not set.");
    $this->assertFalse(isset($ret['test21']), "Nonexistent cache id test21 is not set.");
    // Test values.
    $this->assertIdentical($ret['test2']->data, 3, "Existing cache id test2 has the correct value.");
    $this->assertIdentical($ret['test3']->data, 5, "Existing cache id test3 has the correct value.");
    $this->assertIdentical($ret['test6']->data, 13, "Existing cache id test6 has the correct value.");
    $this->assertIdentical($ret['test7']->data, 17, "Existing cache id test7 has the correct value.");
    // Test $cids array - ensure it contains cache id's that do not exist.
    $this->assert(in_array('test19', $cids), "Nonexistent cache id test19 is in cids array.");
    $this->assert(in_array('test21', $cids), "Nonexistent cache id test21 is in cids array.");
    // Test $cids array - ensure it does not contain cache id's that exist.
    $this->assertFalse(in_array('test2', $cids), "Existing cache id test2 is not in cids array.");
    $this->assertFalse(in_array('test3', $cids), "Existing cache id test3 is not in cids array.");
    $this->assertFalse(in_array('test6', $cids), "Existing cache id test6 is not in cids array.");
    $this->assertFalse(in_array('test7', $cids), "Existing cache id test7 is not in cids array.");

    // Test a second time after deleting and setting new keys which ensures that
    // if the backend uses statics it does not cause unexpected results.
    $backend->delete('test3');
    $backend->delete('test6');
    $backend->set('test19', 57);

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    // Test return - ensure it contains existing cache ids.
    $this->assert(isset($ret['test2']), "Existing cache id test2 is set");
    $this->assert(isset($ret['test7']), "Existing cache id test7 is set");
    $this->assert(isset($ret['test19']), "Added cache id test19 is set");
    // Test return - ensure it does not contain nonexistent cache ids.
    $this->assertFalse(isset($ret['test3']), "Deleted cache id test3 is not set");
    $this->assertFalse(isset($ret['test6']), "Deleted cache id test6 is not set");
    $this->assertFalse(isset($ret['test21']), "Nonexistent cache id test21 is not set");
    // Test values.
    $this->assertIdentical($ret['test2']->data, 3, "Existing cache id test2 has the correct value.");
    $this->assertIdentical($ret['test7']->data, 17, "Existing cache id test7 has the correct value.");
    $this->assertIdentical($ret['test19']->data, 57, "Added cache id test19 has the correct value.");
    // Test $cids array - ensure it contains cache id's that do not exist.
    $this->assert(in_array('test3', $cids), "Deleted cache id test3 is in cids array.");
    $this->assert(in_array('test6', $cids), "Deleted cache id test6 is in cids array.");
    $this->assert(in_array('test21', $cids), "Nonexistent cache id test21 is in cids array.");
    // Test $cids array - ensure it does not contain cache id's that exist.
    $this->assertFalse(in_array('test2', $cids), "Existing cache id test2 is not in cids array.");
    $this->assertFalse(in_array('test7', $cids), "Existing cache id test7 is not in cids array.");
    $this->assertFalse(in_array('test19', $cids), "Added cache id test19 is not in cids array.");

    // Test with a long $cid and non-numeric array key.
    $cids = array('key:key' => $long_cid);
    $return = $backend->getMultiple($cids);
    $this->assertEqual(300, $return[$long_cid]->data);
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
    $items = array(
      'cid_1' => array('data' => 1),
      'cid_2' => array('data' => 2),
      'cid_3' => array('data' => array(1, 2)),
      'cid_4' => array('data' => 1, 'expire' => $future_expiration),
      'cid_5' => array('data' => 1, 'tags' => array('test:a', 'test:b')),
    );
    $backend->setMultiple($items);
    $cids = array_keys($items);
    $cached = $backend->getMultiple($cids);

    $this->assertEqual($cached['cid_1']->data, $items['cid_1']['data'], 'Over-written cache item set correctly.');
    $this->assertTrue($cached['cid_1']->valid, 'Item is marked as valid.');
    $this->assertTrue($cached['cid_1']->created >= REQUEST_TIME && $cached['cid_1']->created <= round(microtime(TRUE), 3), 'Created time is correct.');
    $this->assertEqual($cached['cid_1']->expire, CacheBackendInterface::CACHE_PERMANENT, 'Cache expiration defaults to permanent.');

    $this->assertEqual($cached['cid_2']->data, $items['cid_2']['data'], 'New cache item set correctly.');
    $this->assertEqual($cached['cid_2']->expire, CacheBackendInterface::CACHE_PERMANENT, 'Cache expiration defaults to permanent.');

    $this->assertEqual($cached['cid_3']->data, $items['cid_3']['data'], 'New cache item with serialized data set correctly.');
    $this->assertEqual($cached['cid_3']->expire, CacheBackendInterface::CACHE_PERMANENT, 'Cache expiration defaults to permanent.');

    $this->assertEqual($cached['cid_4']->data, $items['cid_4']['data'], 'New cache item set correctly.');
    $this->assertEqual($cached['cid_4']->expire, $future_expiration, 'Cache expiration has been correctly set.');

    $this->assertEqual($cached['cid_5']->data, $items['cid_5']['data'], 'New cache item set correctly.');
    $this->assertEqual($cached['cid_5']->tags, array('test:a', 'test:b'));

    // Calling ::setMultiple() with invalid cache tags.
    try {
      $items = [
        'exception_test_1' => array('data' => 1, 'tags' => []),
        'exception_test_2' => array('data' => 2, 'tags' => ['valid']),
        'exception_test_3' => array('data' => 3, 'tags' => ['node' => [3, 5, 7]]),
      ];
      $backend->setMultiple($items);
      $this->fail('::setMultiple() was called with invalid cache tags, no exception was thrown.');
    }
    catch (\LogicException $e) {
      $this->pass('::setMultiple() was called with invalid cache tags, an exception was thrown.');
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
    $backend->delete('test23'); // Nonexistent key should not cause an error.
    $backend->deleteMultiple(array(
      'test3',
      'test5',
      'test7',
      'test19', // Nonexistent key should not cause an error.
      'test21', // Nonexistent key should not cause an error.
    ));

    // Test if expected keys have been deleted.
    $this->assertIdentical(FALSE, $backend->get('test1'), "Cache id test1 deleted.");
    $this->assertIdentical(FALSE, $backend->get('test3'), "Cache id test3 deleted.");
    $this->assertIdentical(FALSE, $backend->get('test5'), "Cache id test5 deleted.");
    $this->assertIdentical(FALSE, $backend->get('test7'), "Cache id test7 deleted.");

    // Test if expected keys exist.
    $this->assertNotIdentical(FALSE, $backend->get('test2'), "Cache id test2 exists.");
    $this->assertNotIdentical(FALSE, $backend->get('test4'), "Cache id test4 exists.");
    $this->assertNotIdentical(FALSE, $backend->get('test6'), "Cache id test6 exists.");

    // Test if that expected keys do not exist.
    $this->assertIdentical(FALSE, $backend->get('test19'), "Cache id test19 does not exist.");
    $this->assertIdentical(FALSE, $backend->get('test21'), "Cache id test21 does not exist.");

    // Calling deleteMultiple() with an empty array should not cause an error.
    $this->assertFalse($backend->deleteMultiple(array()));
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::deleteTags().
   */
  function testDeleteTags() {
    $backend = $this->getCacheBackend();

    // Create two cache entries with the same tag and tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Delete test_tag of value 1. This should delete both entries.
    $backend->deleteTags(array('test_tag:2'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two cache items invalidated after deleting a cache tag.');
    $this->assertFalse($backend->get('test_cid_invalidate1', TRUE) || $backend->get('test_cid_invalidate2', TRUE), 'Two cache items deleted after deleting a cache tag.');

    // Create two cache entries with the same tag and an array tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Delete test_tag of value 1. This should delete both entries.
    $backend->deleteTags(array('test_tag:1'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two cache items invalidated after deleted a cache tag.');
    $this->assertFalse($backend->get('test_cid_invalidate1', TRUE) || $backend->get('test_cid_invalidate2', TRUE), 'Two cache items deleted after deleting a cache tag.');

    // Create three cache entries with a mix of tags and tag values.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate3', $this->defaultValue, Cache::PERMANENT, array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2') && $backend->get('test_cid_invalidate3'), 'Three cached items were created.');
    $backend->deleteTags(array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Cached items not matching the tag were not deleted.');
    $this->assertFalse($backend->get('test_cid_invalidated3', TRUE), 'Cache item matching the tag was deleted.');

    // Create cache entry in multiple bins. Two cache entries
    // (test_cid_invalidate1 and test_cid_invalidate2) still exist from previous
    // tests.
    $tags = array('test_tag:1', 'test_tag:2', 'test_tag:3');
    $bins = array('path', 'bootstrap', 'page');
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->set('test', $this->defaultValue, Cache::PERMANENT, $tags);
      $this->assertTrue($this->getCacheBackend($bin)->get('test'), 'Cache item was set in bin.');
    }

    // Delete tag in mulitple bins.
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->deleteTags(array('test_tag:2'));
    }

    // Test that cache entry has been deleted in multple bins.
    foreach ($bins as $bin) {
      $this->assertFalse($this->getCacheBackend($bin)->get('test', TRUE), 'Tag deletion affected item in bin.');
    }
    // Test that the cache entry with a matching tag has been invalidated.
    $this->assertFalse($this->getCacheBackend($bin)->get('test_cid_invalidate2', TRUE), 'Cache items matching tag were deleted.');
    // Test that the cache entry with without a matching tag still exists.
    $this->assertTrue($this->getCacheBackend($bin)->get('test_cid_invalidate1'), 'Cache items not matching tag were not invalidated.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::deleteAll().
   */
  public function testDeleteAll() {
    $backend = $this->getCacheBackend();
    $unrelated = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend->set('test1', 1, Cache::PERMANENT);
    $backend->set('test2', 3, time() + 1000);
    $unrelated->set('test3', 4, Cache::PERMANENT);

    $backend->deleteAll();

    $this->assertFalse($backend->get('test1'), 'First key has been deleted.');
    $this->assertFalse($backend->get('test2'), 'Second key has been deleted.');
    $this->assertTrue($unrelated->get('test3'), 'Item in other bin is preserved.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidate() and
   * Drupal\Core\Cache\CacheBackendInterface::invalidateMultiple().
   */
  function testInvalidate() {
    $backend = $this->getCacheBackend();
    $backend->set('test1', 1);
    $backend->set('test2', 2);
    $backend->set('test3', 2);
    $backend->set('test4', 2);

    $reference = array('test1', 'test2', 'test3', 'test4');

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertEqual(count($ret), 4, 'Four items returned.');

    $backend->invalidate('test1');
    $backend->invalidateMultiple(array('test2', 'test3'));

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    $this->assertEqual(count($ret), 1, 'Only one item element returned.');

    $cids = $reference;
    $ret = $backend->getMultiple($cids, TRUE);
    $this->assertEqual(count($ret), 4, 'Four items returned.');

    // Calling invalidateMultiple() with an empty array should not cause an
    // error.
    $this->assertFalse($backend->invalidateMultiple(array()));
  }

  /**
   * Tests Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  function testInvalidateTags() {
    $backend = $this->getCacheBackend();

    // Create two cache entries with the same tag and tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    $backend->invalidateTags(array('test_tag:2'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two cache items invalidated after invalidating a cache tag.');
    $this->assertTrue($backend->get('test_cid_invalidate1', TRUE) && $backend->get('test_cid_invalidate2', TRUE), 'Cache items not deleted after invalidating a cache tag.');

    // Create two cache entries with the same tag and an array tag value.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Two cache items were created.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    $backend->invalidateTags(array('test_tag:1'));
    $this->assertFalse($backend->get('test_cid_invalidate1') || $backend->get('test_cid_invalidate2'), 'Two caches removed after invalidating a cache tag.');
    $this->assertTrue($backend->get('test_cid_invalidate1', TRUE) && $backend->get('test_cid_invalidate2', TRUE), 'Cache items not deleted after invalidating a cache tag.');

    // Create three cache entries with a mix of tags and tag values.
    $backend->set('test_cid_invalidate1', $this->defaultValue, Cache::PERMANENT, array('test_tag:1'));
    $backend->set('test_cid_invalidate2', $this->defaultValue, Cache::PERMANENT, array('test_tag:2'));
    $backend->set('test_cid_invalidate3', $this->defaultValue, Cache::PERMANENT, array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2') && $backend->get('test_cid_invalidate3'), 'Three cached items were created.');
    $backend->invalidateTags(array('test_tag_foo:3'));
    $this->assertTrue($backend->get('test_cid_invalidate1') && $backend->get('test_cid_invalidate2'), 'Cache items not matching the tag were not invalidated.');
    $this->assertFalse($backend->get('test_cid_invalidated3'), 'Cached item matching the tag was removed.');

    // Create cache entry in multiple bins. Two cache entries
    // (test_cid_invalidate1 and test_cid_invalidate2) still exist from previous
    // tests.
    $tags = array('test_tag:1', 'test_tag:2', 'test_tag:3');
    $bins = array('path', 'bootstrap', 'page');
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->set('test', $this->defaultValue, Cache::PERMANENT, $tags);
      $this->assertTrue($this->getCacheBackend($bin)->get('test'), 'Cache item was set in bin.');
    }

    // Invalidate tag in mulitple bins.
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->invalidateTags(array('test_tag:2'));
    }

    // Test that cache entry has been invalidated in multple bins.
    foreach ($bins as $bin) {
      $this->assertFalse($this->getCacheBackend($bin)->get('test'), 'Tag invalidation affected item in bin.');
    }
    // Test that the cache entry with a matching tag has been invalidated.
    $this->assertFalse($this->getCacheBackend($bin)->get('test_cid_invalidate2'), 'Cache items matching tag were invalidated.');
    // Test that the cache entry with without a matching tag still exists.
    $this->assertTrue($this->getCacheBackend($bin)->get('test_cid_invalidate1'), 'Cache items not matching tag were not invalidated.');
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidateAll().
   */
  public function testInvalidateAll() {
    $backend = $this->getCacheBackend();
    $unrelated = $this->getCacheBackend('bootstrap');

    // Set both expiring and permanent keys.
    $backend->set('test1', 1, Cache::PERMANENT);
    $backend->set('test2', 3, time() + 1000);
    $unrelated->set('test3', 4, Cache::PERMANENT);

    $backend->invalidateAll();

    $this->assertFalse($backend->get('test1'), 'First key has been invalidated.');
    $this->assertFalse($backend->get('test2'), 'Second key has been invalidated.');
    $this->assertTrue($unrelated->get('test3'), 'Item in other bin is preserved.');
    $this->assertTrue($backend->get('test1', TRUE), 'First key has not been deleted.');
    $this->assertTrue($backend->get('test2', TRUE), 'Second key has not been deleted.');
  }

}
