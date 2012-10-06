<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\DatabaseStorageExpirableTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

/**
 * Tests the key-value database storage.
 */
class DatabaseStorageExpirableTest extends StorageTestBase {

  /**
   * The name of the class to test.
   *
   * The tests themselves are in StorageTestBase and use this class.
   */
  protected $storageClass = 'Drupal\Core\KeyValueStore\DatabaseStorageExpirable';

  public static function getInfo() {
    return array(
      'name' => 'Expirable database storage',
      'description' => 'Tests the expirable key-value database storage.',
      'group' => 'Key-value store',
    );
  }

  protected function setUp() {
    parent::setUp();
    module_load_install('system');
    $schema = system_schema();
    db_create_table('key_value_expire', $schema['key_value_expire']);
  }

  protected function tearDown() {
    db_drop_table('key_value_expire');
    parent::tearDown();
  }

  /**
   * Tests CRUD functionality with expiration.
   */
  public function testCRUDWithExpiration() {
    // Verify that an item can be stored with setWithExpire().
    // Use a random expiration in each test.
    $this->store1->setWithExpire('foo', $this->objects[0], rand(500, 299792458));
    $this->assertIdenticalObject($this->objects[0], $this->store1->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that an item can be updated with setWithExpire().
    $this->store1->setWithExpire('foo', $this->objects[1], rand(500, 299792458));
    $this->assertIdenticalObject($this->objects[1], $this->store1->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that the expirable data key is unique.
    $this->store2->setWithExpire('foo', $this->objects[2], rand(500, 299792458));
    $this->assertIdenticalObject($this->objects[1], $this->store1->get('foo'));
    $this->assertIdenticalObject($this->objects[2], $this->store2->get('foo'));

    // Verify that multiple items can be stored with setMultipleWithExpire().
    $values = array(
      'foo' => $this->objects[3],
      'bar' => $this->objects[4],
    );
    $this->store1->setMultipleWithExpire($values, rand(500, 299792458));
    $result = $this->store1->getMultiple(array('foo', 'bar'));
    foreach ($values as $j => $value) {
      $this->assertIdenticalObject($value, $result[$j]);
    }

    // Verify that the other collection was not affected.
    $this->assertIdenticalObject($this->store2->get('foo'), $this->objects[2]);
    $this->assertFalse($this->store2->get('bar'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $this->store2->set('foo', $this->objects[5]);
    $result = $this->store1->getAll();
    // Not using assertIdentical(), since the order is not defined for getAll().
    $this->assertEqual(count($result), count($values));
    foreach ($result as $key => $value) {
      $this->assertEqual($values[$key], $value);
    }
    // Verify that all items in the other collection are different.
    $result = $this->store2->getAll();
    $this->assertEqual($result, array('foo' => $this->objects[5]));

    // Verify that multiple items can be deleted.
    $this->store1->deleteMultiple(array_keys($values));
    $this->assertFalse($this->store1->get('foo'));
    $this->assertFalse($this->store1->get('bar'));
    $this->assertFalse($this->store1->getMultiple(array('foo', 'bar')));
    // Verify that the item in the other collection still exists.
    $this->assertIdenticalObject($this->objects[5], $this->store2->get('foo'));

    // Test that setWithExpireIfNotExists() succeeds only the first time.
    $key = $this->randomName();
    for ($i = 0; $i <= 1; $i++) {
      // setWithExpireIfNotExists() should be TRUE the first time (when $i is
      // 0) and FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $this->store1->setWithExpireIfNotExists($key, $this->objects[$i], rand(500, 299792458)));
      $this->assertIdenticalObject($this->objects[0], $this->store1->get($key));
      // Verify that the other collection is not affected.
      $this->assertFalse($this->store2->get($key));
    }

    // Remove the item and try to set it again.
    $this->store1->delete($key);
    $this->store1->setWithExpireIfNotExists($key, $this->objects[1], rand(500, 299792458));
    // This time it should succeed.
    $this->assertIdenticalObject($this->objects[1], $this->store1->get($key));
    // Verify that the other collection is still not affected.
    $this->assertFalse($this->store2->get($key));

  }

  /**
   * Tests data expiration and garbage collection.
   */
  public function testExpiration() {
    $day = 604800;

    // Set an item to expire in the past and another without an expiration.
    $this->store1->setWithExpire('yesterday', 'all my troubles seemed so far away', -1 * $day);
    $this->store1->set('troubles', 'here to stay');

    // Only the non-expired item should be returned.
    $this->assertFalse($this->store1->get('yesterday'));
    $this->assertIdentical($this->store1->get('troubles'), 'here to stay');
    $this->assertIdentical(count($this->store1->getMultiple(array('yesterday', 'troubles'))), 1);

    // Store items set to expire in the past in various ways.
    $this->store1->setWithExpire($this->randomName(), $this->objects[0], -7 * $day);
    $this->store1->setWithExpireIfNotExists($this->randomName(), $this->objects[1], -5 * $day);
    $this->store1->setMultipleWithExpire(
      array(
        $this->randomName() => $this->objects[2],
        $this->randomName() => $this->objects[3],
      ),
      -3 * $day
    );
    $this->store1->setWithExpireIfNotExists('yesterday', "you'd forgiven me", -1 * $day);
    $this->store1->setWithExpire('still', "'til we say we're sorry", 2 * $day);

    // Ensure only non-expired items are retrived.
    $all = $this->store1->getAll();
    $this->assertIdentical(count($all), 2);
    foreach (array('troubles', 'still') as $key) {
      $this->assertTrue(!empty($all[$key]));
    }

    // Perform garbage collection and confirm that the expired items are
    // deleted from the database.
    $this->store1->garbageCollection();
    $result = db_query(
      'SELECT name, value FROM {key_value_expire} WHERE collection = :collection',
      array(
        ':collection' => $this->collection1,
      ))->fetchAll();
    $this->assertIdentical(sizeof($result), 2);
  }

}
