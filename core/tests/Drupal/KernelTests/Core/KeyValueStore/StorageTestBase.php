<?php

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for testing key-value storages.
 */
abstract class StorageTestBase extends KernelTestBase {

  /**
   * An array of random stdClass objects.
   *
   * @var array
   */
  protected $objects = [];

  /**
   * An array of data collection labels.
   *
   * @var array
   */
  protected $collections = [];

  /**
   * Whether we are using an expirable key/value store.
   *
   * @var bool
   */
  protected $factory = 'keyvalue';

  protected function setUp() {
    parent::setUp();

    // Define two data collections,
    $this->collections = [0 => 'zero', 1 => 'one'];

    // Create several objects for testing.
    for ($i = 0; $i <= 5; $i++) {
      $this->objects[$i] = $this->randomObject();
    }
  }

  /**
   * Tests CRUD operations.
   */
  public function testCRUD() {
    $stores = $this->createStorage();
    // Verify that each store returns its own collection name.
    $this->assertSame($this->collections[0], $stores[0]->getCollectionName());
    $this->assertSame($this->collections[1], $stores[1]->getCollectionName());

    // Verify that an item can be stored.
    $stores[0]->set('foo', $this->objects[0]);
    $this->assertTrue($stores[0]->has('foo'));
    $this->assertEquals($this->objects[0], $stores[0]->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertFalse($stores[1]->has('foo'));
    $this->assertNull($stores[1]->get('foo'));

    // Verify that an item can be updated.
    $stores[0]->set('foo', $this->objects[1]);
    $this->assertEquals($this->objects[1], $stores[0]->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertNull($stores[1]->get('foo'));

    // Verify that a collection/name pair is unique.
    $stores[1]->set('foo', $this->objects[2]);
    $this->assertEquals($this->objects[1], $stores[0]->get('foo'));
    $this->assertEquals($this->objects[2], $stores[1]->get('foo'));

    // Verify that an item can be deleted.
    $stores[0]->delete('foo');
    $this->assertFalse($stores[0]->has('foo'));
    $this->assertNull($stores[0]->get('foo'));

    // Verify that the other collection is not affected.
    $this->assertTrue($stores[1]->has('foo'));
    $this->assertEquals($this->objects[2], $stores[1]->get('foo'));
    $stores[1]->delete('foo');
    $this->assertNull($stores[1]->get('foo'));

    // Verify that multiple items can be stored.
    $values = [
      'foo' => $this->objects[3],
      'bar' => $this->objects[4],
    ];
    $stores[0]->setMultiple($values);

    // Verify that multiple items can be retrieved.
    $result = $stores[0]->getMultiple(['foo', 'bar']);
    foreach ($values as $j => $value) {
      $this->assertEquals($value, $result[$j]);
    }

    // Verify that the other collection was not affected.
    $this->assertNull($stores[1]->get('foo'));
    $this->assertNull($stores[1]->get('bar'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $stores[1]->set('foo', $this->objects[5]);

    // Not using assertSame(), since the order is not defined for getAll().
    $this->assertEquals($values, $stores[0]->getAll());

    // Verify that all items in the other collection are different.
    $result = $stores[1]->getAll();
    $this->assertEquals(['foo' => $this->objects[5]], $result);

    // Verify that multiple items can be deleted.
    $stores[0]->deleteMultiple(array_keys($values));
    $this->assertNull($stores[0]->get('foo'));
    $this->assertNull($stores[0]->get('bar'));
    $this->assertEmpty($stores[0]->getMultiple(['foo', 'bar']));
    // Verify that deleting no items does not cause an error.
    $stores[0]->deleteMultiple([]);
    // Verify that the item in the other collection still exists.
    $this->assertEquals($this->objects[5], $stores[1]->get('foo'));

  }

  /**
   * Tests expected behavior for non-existing keys.
   */
  public function testNonExistingKeys() {

    $stores = $this->createStorage();

    // Verify that a non-existing key returns NULL as value.
    $this->assertNull($stores[0]->get('foo'));

    // Verify that a non-existing key with a default returns the default.
    $this->assertSame('bar', $stores[0]->get('foo', 'bar'));

    // Verify that a FALSE value can be stored.
    $stores[0]->set('foo', FALSE);
    $this->assertFalse($stores[0]->get('foo'));

    // Verify that a deleted key returns NULL as value.
    $stores[0]->delete('foo');
    $this->assertNull($stores[0]->get('foo'));

    // Verify that a non-existing key is not returned when getting multiple keys.
    $stores[0]->set('bar', 'baz');
    $values = $stores[0]->getMultiple(['foo', 'bar']);
    $this->assertFalse(isset($values['foo']), "Key 'foo' not found.");
    $this->assertSame('baz', $values['bar']);
  }

  /**
   * Tests the setIfNotExists() method.
   */
  public function testSetIfNotExists() {
    $stores = $this->createStorage();

    $key = $this->randomMachineName();
    // Test that setIfNotExists() succeeds only the first time.
    for ($i = 0; $i <= 1; $i++) {
      // setIfNotExists() should be TRUE the first time (when $i is 0) and
      // FALSE the second time (when $i is 1).
      $this->assertEquals(!$i, $stores[0]->setIfNotExists($key, $this->objects[$i]));
      $this->assertEquals($this->objects[0], $stores[0]->get($key));
      // Verify that the other collection is not affected.
      $this->assertNull($stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $stores[0]->delete($key);
    $stores[0]->setIfNotExists($key, $this->objects[1]);
    // This time it should succeed.
    $this->assertEquals($this->objects[1], $stores[0]->get($key));
    // Verify that the other collection is still not affected.
    $this->assertNull($stores[1]->get($key));
  }

  /**
   * Tests the rename operation.
   */
  public function testRename() {
    $stores = $this->createStorage();
    $store = $stores[0];

    $store->set('old', 'thing');
    $this->assertSame('thing', $store->get('old'));
    $store->rename('old', 'new');
    $this->assertSame('thing', $store->get('new'));
    $this->assertNull($store->get('old'));
  }

  /**
   * Creates storage objects for each collection defined for this class.
   *
   * Storing the storage objects in a class member variable causes a fatal
   * exception in DatabaseStorageExpirableTest, because in that situation
   * garbage collection is not triggered until the test class itself is
   * destructed, after tearDown() has deleted the database tables. Instead,
   * create the storage objects locally in each test using this method.
   *
   * @see \Drupal\system\Tests\KeyValueStore\DatabaseStorageExpirable
   * @see \Drupal\Core\KeyValueStore\DatabaseStorageExpirable::garbageCollection()
   */
  protected function createStorage() {
    $stores = [];
    foreach ($this->collections as $i => $collection) {
      $stores[$i] = $this->container->get($this->factory)->get($collection);
    }

    return $stores;
  }

}
