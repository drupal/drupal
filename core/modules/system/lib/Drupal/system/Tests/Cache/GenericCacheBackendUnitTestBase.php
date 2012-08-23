<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Cache\GenericCacheBackendUnitTestBase.
 */

namespace Drupal\system\Tests\Cache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\simpletest\UnitTestBase;

use stdClass;

/**
 * Full generic unit test suite for any cache backend. In order to use it for a
 * cache backend implementation extend this class and override the
 * createBackendInstace() method to return an object.
 *
 * @see DatabaseBackendUnitTestCase
 *   For a full working implementation.
 */
abstract class GenericCacheBackendUnitTestBase extends UnitTestBase {

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
   * Get testing bin.
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
   * Create a cache backend to test.
   *
   * Override this method to test a CacheBackend.
   *
   * @param string $bin
   *   Bin name to use for this backend instance.
   *
   * @return Drupal\Core\Cache\CacheBackendInterface
   *   Cache backend to test.
   */
  protected abstract function createCacheBackend($bin);

  /**
   * Allow specific implementation to change the environement before test run.
   */
  public function setUpCacheBackend() {
  }

  /**
   * Allow specific implementation to alter the environement after test run but
   * before the real tear down, which will changes things such as the database
   * prefix.
   */
  public function tearDownCacheBackend() {
  }

  /**
   * Get backend to test, this will get a shared instance set in the object.
   *
   * @return Drupal\Core\Cache\CacheBackendInterface
   *   Cache backend to test.
   */
  final function getCacheBackend($bin = null) {
    if (!isset($bin)) {
      $bin = $this->getTestBin();
    }
    if (!isset($this->cachebackends[$bin])) {
      $this->cachebackends[$bin] = $this->createCacheBackend($bin);
      // Ensure the backend is empty.
      $this->cachebackends[$bin]->flush();
    }
    return $this->cachebackends[$bin];
  }

  public function setUp() {
    $this->cachebackends = array();
    $this->defaultValue = $this->randomName(10);

    parent::setUp();

    $this->setUpCacheBackend();
  }

  public function tearDown() {
    // Destruct the registered backend, each test will get a fresh instance,
    // properly flushing it here ensure that on persistant data backends they
    // will come up empty the next test.
    foreach ($this->cachebackends as $bin => $cachebackend) {
      $this->cachebackends[$bin]->flush();
    }
    unset($this->cachebackends);

    $this->tearDownCacheBackend();

    parent::tearDown();
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::get() and
   * Drupal\Core\Cache\CacheBackendInterface::set().
   */
  public function testSetGet() {
    $backend = $this->getCacheBackend();

    $data = 7;
    $this->assertIdentical(FALSE, $backend->get('test1'), "Backend does not contain data for cache id test1.");
    $backend->set('test1', $data);
    $cached = $backend->get('test1');
    $this->assert(is_object($cached), "Backend returned an object for cache id test1.");
    $this->assertIdentical($data, $cached->data);

    $data = array('value' => 3);
    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2.");
    $backend->set('test2', $data);
    $cached = $backend->get('test2');
    $this->assert(is_object($cached), "Backend returned an object for cache id test2.");
    $this->assertIdentical($data, $cached->data);
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::delete().
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

    $cached = $backend->get('test2');
    $this->assert(is_object($backend->get('test2')), "Backend still has an object for cache id test2.");

    $backend->delete('test2');
    $this->assertIdentical(FALSE, $backend->get('test2'), "Backend does not contain data for cache id test2 after deletion.");
  }

  /**
   * Test data type perservation.
   */
  public function testValueTypeIsKept() {
    $backend = $this->getCacheBackend();

    $variables = array(
      'test1' => 1,
      'test2' => '0',
      'test3' => '',
      'test4' => 12.64,
      'test5' => false,
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
   * Test Drupal\Core\Cache\CacheBackendInterface::getMultiple().
   */
  public function testGetMultiple() {
    $backend = $this->getCacheBackend();

    // Set numerous testing keys.
    $backend->set('test1', 1);
    $backend->set('test2', 3);
    $backend->set('test3', 5);
    $backend->set('test4', 7);
    $backend->set('test5', 11);
    $backend->set('test6', 13);
    $backend->set('test7', 17);

    // Mismatch order for harder testing.
    $reference = array(
      'test3',
      'test7',
      'test21', // Cid does not exist.
      'test6',
      'test19', // Cid does not exist until added before second getMulitple().
      'test2',
    );

    $cids = $reference;
    $ret = $backend->getMultiple($cids);
    // Test return - ensure it contains existing cache ids.
    $this->assert(isset($ret['test2']), "Existing cache id test2 is set.");
    $this->assert(isset($ret['test3']), "Existing cache id test3 is set.");
    $this->assert(isset($ret['test6']), "Existing cache id test6 is set.");
    $this->assert(isset($ret['test7']), "Existing cache id test7 is set.");
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
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::isEmpty().
   */
  public function testIsEmpty() {
    $backend = $this->getCacheBackend();

    $this->assertTrue($backend->isEmpty(), "Backend is empty.");

    $backend->set('pony', "Shetland");
    $this->assertFalse($backend->isEmpty(), "Backend is not empty.");

    $backend->delete('pony');
    $this->assertTrue($backend->isEmpty(), "Backend is empty.");
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::deleteMultiple().
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

    $backend->deleteMultiple(array(
      'test1',
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
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::deletePrefix().
   */
  public function testDeletePrefix() {
    $backend = $this->getCacheBackend();

    // Set numerous testing keys.
    $backend->set('banana_test1', 1);
    $backend->set('monkey_test2', 3);
    $backend->set('monkey_banana_test3', 5);
    $backend->set('banana_test_4', 7);
    $backend->set('pony_monkey_test5_banana', 11);
    $backend->set('monkey_test6', 13);
    $backend->set('banana_pony_test7_monkey', 17);

    $backend->deletePrefix('banana');
    // Keys starting with banana have been deleted.
    $this->assertIdentical(FALSE, $backend->get('banana_test1'), "Cache id banana_test1 deleted.");
    $this->assertIdentical(FALSE, $backend->get('banana_test_4'), "Cache id banana_test_4 deleted.");
    $this->assertIdentical(FALSE, $backend->get('banana_pony_test7_monkey'), "Cache id banana_pony_test7_monkey deleted.");
    // Keys not starting with banana still exist.
    $this->assertNotIdentical(FALSE, $backend->get('monkey_test2'), "Cache id monkey_test2 exists.");
    $this->assertNotIdentical(FALSE, $backend->get('monkey_banana_test3'), "Cache id monkey_banana_test3 exists.");
    $this->assertNotIdentical(FALSE, $backend->get('pony_monkey_test5_banana'), "Cache id poney_monkey_test5_banana exists.");
    $this->assertNotIdentical(FALSE, $backend->get('monkey_test6'), "Cache id monkey_test6 exists.");

    $backend->deletePrefix('monkey');
    // Keys starting with monkey have been deleted.
    $this->assertIdentical(FALSE, $backend->get('monkey_test2'), "Cache id monkey_test2 deleted.");
    $this->assertIdentical(FALSE, $backend->get('monkey_banana_test3'), "Cache id monkey_banana_test3 deleted.");
    $this->assertIdentical(FALSE, $backend->get('banana_pony_test7_monkey'), "Cache id banana_pony_test7_monkey deleted.");
    // Keys not starting with monkey still exist.
    $this->assertNotIdentical(FALSE, $backend->get('pony_monkey_test5_banana'), "Cache id pony_monkey_test5_banana exists.");
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::flush().
   */
  public function testFlush() {
    $backend = $this->getCacheBackend();

    // Set both expiring and permanent keys.
    $backend->set('test1', 1, CACHE_PERMANENT);
    $backend->set('test2', 3, time() + 1000);

    $backend->flush();

    $this->assertTrue($backend->isEmpty(), "Backend is empty after flush.");

    $this->assertIdentical(FALSE, $backend->get('test1'), "First key has been flushed.");
    $this->assertIdentical(FALSE, $backend->get('test2'), "Second key has been flushed.");
  }

  /**
   * Check whether or not a cache entry exists.
   *
   * @param $cid
   *   The cache id.
   * @param $bin
   *   The cache bin to use. If not provided the default test bin wil be used.
   *
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function checkCacheExists($cid, $bin = null) {
    $cached = $this->getCacheBackend($bin)->get($cid);
    return isset($cached->data);
  }

  /**
   * Test Drupal\Core\Cache\CacheBackendInterface::invalidateTags().
   */
  function testClearTags() {
    $backend = $this->getCacheBackend();

    // Create two cache entries with the same tag and tag value.
    $backend->set('test_cid_clear1', $this->defaultValue, CACHE_PERMANENT, array('test_tag' => array(1)));
    $backend->set('test_cid_clear2', $this->defaultValue, CACHE_PERMANENT, array('test_tag' => array(1)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1')
                      && $this->checkCacheExists('test_cid_clear2'),
                      'Two cache items were created.');
    // Invalidate test_tag of value 1. This should invalidate both entries.
    $backend->invalidateTags(array('test_tag' => array(1)));
    $this->assertFalse($this->checkCacheExists('test_cid_clear1')
                      || $this->checkCacheExists('test_cid_clear2'),
                      'Two caches removed after clearing a cache tag.');

    // Create three cache entries with a mix of tags and tag values.
    $backend->set('test_cid_clear1', $this->defaultValue, CACHE_PERMANENT, array('test_tag' => array(1)));
    $backend->set('test_cid_clear2', $this->defaultValue, CACHE_PERMANENT, array('test_tag' => array(2)));
    $backend->set('test_cid_clear3', $this->defaultValue, CACHE_PERMANENT, array('test_tag_foo' => array(3)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1')
                      && $this->checkCacheExists('test_cid_clear2')
                      && $this->checkCacheExists('test_cid_clear3'),
                      'Two cached items were created.');
    $backend->invalidateTags(array('test_tag_foo' => array(3)));
    $this->assertTrue($this->checkCacheExists('test_cid_clear1')
                      && $this->checkCacheExists('test_cid_clear2'),
                      'Cached items not matching the tag were not cleared.');

    $this->assertFalse($this->checkCacheExists('test_cid_clear3'),
                      'Cached item matching the tag was removed.');

    // Create cache entry in multiple bins. Two cache entries (test_cid_clear1
    // and test_cid_clear2) still exist from previous tests.
    $tags = array('test_tag' => array(1, 2, 3));
    $bins = array('path', 'bootstrap', 'page');
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->set('test', $this->defaultValue, CACHE_PERMANENT, $tags);
      $this->assertTrue($this->checkCacheExists('test', $bin), 'Cache item was set in bin.');
    }

    // Invalidate tag in mulitple bins.
    foreach ($bins as $bin) {
      $this->getCacheBackend($bin)->invalidateTags(array('test_tag' => array(2)));
    }

    // Test that cache entry has been invalidated in multple bins.
    foreach ($bins as $bin) {
      $this->assertFalse($this->checkCacheExists('test', $bin), 'Tag expire affected item in bin.');
    }
    // Test that the cache entry with a matching tag has been invalidated.
    $this->assertFalse($this->checkCacheExists('test_cid_clear2', $bin), 'Cached items matching tag were cleared.');
    // Test that the cache entry with without a matching tag still exists.
    $this->assertTrue($this->checkCacheExists('test_cid_clear1', $bin), 'Cached items not matching tag were not cleared.');
  }
}
