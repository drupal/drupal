<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\StorageTestBase.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\simpletest\UnitTestBase;

/**
 * Base class for testing key-value storages.
 */
abstract class StorageTestBase extends UnitTestBase {

  /**
   * The fully qualified class name of the key-value storage to test.
   *
   * @var string
   */
  protected $storageClass;

  protected function setUp() {
    parent::setUp();

    $this->collection1 = 'first';
    $this->collection2 = 'second';

    $this->store1 = new $this->storageClass($this->collection1);
    $this->store2 = new $this->storageClass($this->collection2);
  }

  /**
   * Tests CRUD operations.
   */
  public function testCRUD() {
    // Verify that each store returns its own collection name.
    $this->assertIdentical($this->store1->getCollectionName(), $this->collection1);
    $this->assertIdentical($this->store2->getCollectionName(), $this->collection2);

    // Verify that an item can be stored.
    $this->store1->set('foo', 'bar');
    $this->assertIdentical('bar', $this->store1->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that an item can be updated.
    $this->store1->set('foo', 'baz');
    $this->assertIdentical('baz', $this->store1->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertFalse($this->store2->get('foo'));

    // Verify that a collection/name pair is unique.
    $this->store2->set('foo', 'other');
    $this->assertIdentical('baz', $this->store1->get('foo'));
    $this->assertIdentical('other', $this->store2->get('foo'));

    // Verify that an item can be deleted.
    $this->store1->delete('foo');
    $this->assertFalse($this->store1->get('foo'));

    // Verify that the other collection is not affected.
    $this->assertIdentical('other', $this->store2->get('foo'));
    $this->store2->delete('foo');
    $this->assertFalse($this->store2->get('foo'));

    // Verify that multiple items can be stored.
    $values = array(
      'foo' => 'bar',
      'baz' => 'qux',
    );
    $this->store1->setMultiple($values);

    // Verify that multiple items can be retrieved.
    $result = $this->store1->getMultiple(array('foo', 'baz'));
    $this->assertIdentical($values, $result);

    // Verify that the other collection was not affected.
    $this->assertFalse($this->store2->get('foo'));
    $this->assertFalse($this->store2->get('baz'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $this->store2->set('foo', 'other');
    $result = $this->store1->getAll();
    // Not using assertIdentical(), since the order is not defined for getAll().
    $this->assertEqual(count($result), count($values));
    foreach ($result as $key => $value) {
      $this->assertEqual($values[$key], $value);
    }
    // Verify that all items in the other collection are different.
    $result = $this->store2->getAll();
    $this->assertEqual($result, array('foo' => 'other'));

    // Verify that multiple items can be deleted.
    $this->store1->deleteMultiple(array_keys($values));
    $this->assertFalse($this->store1->get('foo'));
    $this->assertFalse($this->store1->get('bar'));
    $this->assertFalse($this->store1->getMultiple(array('foo', 'baz')));
    // Verify that the item in the other collection still exists.
    $this->assertIdentical('other', $this->store2->get('foo'));
  }

  /**
   * Tests expected behavior for non-existing keys.
   */
  public function testNonExistingKeys() {
    // Verify that a non-existing key returns NULL as value.
    $this->assertNull($this->store1->get('foo'));

    // Verify that a FALSE value can be stored.
    $this->store1->set('foo', FALSE);
    $this->assertIdentical($this->store1->get('foo'), FALSE);

    // Verify that a deleted key returns NULL as value.
    $this->store1->delete('foo');
    $this->assertNull($this->store1->get('foo'));

    // Verify that a non-existing key is not returned when getting multiple keys.
    $this->store1->set('bar', 'baz');
    $values = $this->store1->getMultiple(array('foo', 'bar'));
    $this->assertFalse(isset($values['foo']), "Key 'foo' not found.");
    $this->assertIdentical($values['bar'], 'baz');
  }
}
