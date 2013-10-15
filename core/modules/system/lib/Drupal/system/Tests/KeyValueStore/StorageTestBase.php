<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\StorageTestBase.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\simpletest\UnitTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Base class for testing key-value storages.
 */
abstract class StorageTestBase extends UnitTestBase {

  /**
   * An array of random stdClass objects.
   *
   * @var array
   */
  protected $objects = array();

  /**
   * An array of data collection labels.
   *
   * @var array
   */
  protected $collections = array();

  /**
   * Whether we are using an expirable key/value store.
   *
   * @var boolean
   */
  protected $factory = 'keyvalue';

  /**
   * A container for the services needed in these tests.
   *
   * @var ContainerBuilder
   */
  protected $container;

  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container
      ->register('service_container', 'Symfony\Component\DependencyInjection\ContainerBuilder')
      ->setSynthetic(TRUE);
    $this->container->set('service_container', $this->container);
    $this->container->register('settings', 'Drupal\Component\Utility\Settings')
      ->setFactoryClass('Drupal\Component\Utility\Settings')
      ->setFactoryMethod('getSingleton');
    $this->container
      ->register('keyvalue', 'Drupal\Core\KeyValueStore\KeyValueFactory')
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('settings'));
    $this->container
      ->register('keyvalue.expirable', 'Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('settings'));
    // Define two data collections,
    $this->collections = array(0 => 'zero', 1 => 'one');

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
    $this->assertIdentical($stores[0]->getCollectionName(), $this->collections[0]);
    $this->assertIdentical($stores[1]->getCollectionName(), $this->collections[1]);

    // Verify that an item can be stored.
    $stores[0]->set('foo', $this->objects[0]);
    $this->assertIdenticalObject($this->objects[0], $stores[0]->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertFalse($stores[1]->get('foo'));

    // Verify that an item can be updated.
    $stores[0]->set('foo', $this->objects[1]);
    $this->assertIdenticalObject($this->objects[1], $stores[0]->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertFalse($stores[1]->get('foo'));

    // Verify that a collection/name pair is unique.
    $stores[1]->set('foo', $this->objects[2]);
    $this->assertIdenticalObject($this->objects[1], $stores[0]->get('foo'));
    $this->assertIdenticalObject($this->objects[2], $stores[1]->get('foo'));

    // Verify that an item can be deleted.
    $stores[0]->delete('foo');
    $this->assertFalse($stores[0]->get('foo'));

    // Verify that the other collection is not affected.
    $this->assertIdenticalObject($this->objects[2], $stores[1]->get('foo'));
    $stores[1]->delete('foo');
    $this->assertFalse($stores[1]->get('foo'));

    // Verify that multiple items can be stored.
    $values = array(
      'foo' => $this->objects[3],
      'bar' => $this->objects[4],
    );
    $stores[0]->setMultiple($values);

    // Verify that multiple items can be retrieved.
    $result = $stores[0]->getMultiple(array('foo', 'bar'));
    foreach ($values as $j => $value) {
      $this->assertIdenticalObject($value, $result[$j]);
    }

    // Verify that the other collection was not affected.
    $this->assertFalse($stores[1]->get('foo'));
    $this->assertFalse($stores[1]->get('bar'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $stores[1]->set('foo', $this->objects[5]);
    $result = $stores[0]->getAll();
    // Not using assertIdentical(), since the order is not defined for getAll().
    $this->assertEqual(count($result), count($values));
    foreach ($result as $key => $value) {
      $this->assertEqual($values[$key], $value);
    }
    // Verify that all items in the other collection are different.
    $result = $stores[1]->getAll();
    $this->assertEqual($result, array('foo' => $this->objects[5]));

    // Verify that multiple items can be deleted.
    $stores[0]->deleteMultiple(array_keys($values));
    $this->assertFalse($stores[0]->get('foo'));
    $this->assertFalse($stores[0]->get('bar'));
    $this->assertFalse($stores[0]->getMultiple(array('foo', 'bar')));
    // Verify that the item in the other collection still exists.
    $this->assertIdenticalObject($this->objects[5], $stores[1]->get('foo'));

  }

  /**
   * Tests expected behavior for non-existing keys.
   */
  public function testNonExistingKeys() {

    $stores = $this->createStorage();

    // Verify that a non-existing key returns NULL as value.
    $this->assertNull($stores[0]->get('foo'));

    // Verify that a non-existing key with a default returns the default.
    $this->assertIdentical($stores[0]->get('foo', 'bar'), 'bar');

    // Verify that a FALSE value can be stored.
    $stores[0]->set('foo', FALSE);
    $this->assertIdentical($stores[0]->get('foo'), FALSE);

    // Verify that a deleted key returns NULL as value.
    $stores[0]->delete('foo');
    $this->assertNull($stores[0]->get('foo'));

    // Verify that a non-existing key is not returned when getting multiple keys.
    $stores[0]->set('bar', 'baz');
    $values = $stores[0]->getMultiple(array('foo', 'bar'));
    $this->assertFalse(isset($values['foo']), "Key 'foo' not found.");
    $this->assertIdentical($values['bar'], 'baz');
  }

  /**
   * Tests the setIfNotExists() method.
   */
  public function testSetIfNotExists() {
    $stores = $this->createStorage();

    $key = $this->randomName();
    // Test that setIfNotExists() succeeds only the first time.
    for ($i = 0; $i <= 1; $i++) {
      // setIfNotExists() should be TRUE the first time (when $i is 0) and
      // FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $stores[0]->setIfNotExists($key, $this->objects[$i]));
      $this->assertIdenticalObject($this->objects[0], $stores[0]->get($key));
      // Verify that the other collection is not affected.
      $this->assertFalse($stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $stores[0]->delete($key);
    $stores[0]->setIfNotExists($key, $this->objects[1]);
    // This time it should succeed.
    $this->assertIdenticalObject($this->objects[1], $stores[0]->get($key));
    // Verify that the other collection is still not affected.
    $this->assertFalse($stores[1]->get($key));
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
    $stores = array();
    foreach ($this->collections as $i => $collection) {
      $stores[$i] = $this->container->get($this->factory)->get($collection);
    }

    return $stores;
  }

}
