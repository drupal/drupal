<?php

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\KeyValueStore\KeyValueFactory;

/**
 * Tests the key-value database storage.
 *
 * @group KeyValueStore
 */
class DatabaseStorageExpirableTest extends StorageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  protected function setUp(): void {
    parent::setUp();
    $this->factory = 'keyvalue.expirable';
    $this->installSchema('system', ['key_value_expire']);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.expirable.database';
    $container->setParameter('factory.keyvalue.expirable', $parameter);
  }

  /**
   * Tests CRUD functionality with expiration.
   */
  public function testCRUDWithExpiration() {
    $stores = $this->createStorage();

    // Verify that an item can be stored with setWithExpire().
    // Use a random expiration in each test.
    $stores[0]->setWithExpire('foo', $this->objects[0], rand(500, 100000));
    $this->assertEquals($this->objects[0], $stores[0]->get('foo'));
    // Verify that the other collection is not affected.
    $this->assertNull($stores[1]->get('foo'));

    // Verify that an item can be updated with setWithExpire().
    $stores[0]->setWithExpire('foo', $this->objects[1], rand(500, 100000));
    $this->assertEquals($this->objects[1], $stores[0]->get('foo'));
    // Verify that the other collection is still not affected.
    $this->assertNull($stores[1]->get('foo'));

    // Verify that the expirable data key is unique.
    $stores[1]->setWithExpire('foo', $this->objects[2], rand(500, 100000));
    $this->assertEquals($this->objects[1], $stores[0]->get('foo'));
    $this->assertEquals($this->objects[2], $stores[1]->get('foo'));

    // Verify that multiple items can be stored with setMultipleWithExpire().
    $values = [
      'foo' => $this->objects[3],
      'bar' => $this->objects[4],
    ];
    $stores[0]->setMultipleWithExpire($values, rand(500, 100000));
    $result = $stores[0]->getMultiple(['foo', 'bar']);
    foreach ($values as $j => $value) {
      $this->assertEquals($value, $result[$j]);
    }

    // Verify that the other collection was not affected.
    $this->assertEquals($this->objects[2], $stores[1]->get('foo'));
    $this->assertNull($stores[1]->get('bar'));

    // Verify that all items in a collection can be retrieved.
    // Ensure that an item with the same name exists in the other collection.
    $stores[1]->set('foo', $this->objects[5]);
    $result = $stores[0]->getAll();
    // Not using assertSame(), since the order is not defined for getAll().
    $this->assertEqual(count($result), count($values));
    foreach ($result as $key => $value) {
      $this->assertEqual($values[$key], $value);
    }
    // Verify that all items in the other collection are different.
    $result = $stores[1]->getAll();
    $this->assertEqual($result, ['foo' => $this->objects[5]]);

    // Verify that multiple items can be deleted.
    $stores[0]->deleteMultiple(array_keys($values));
    $this->assertNull($stores[0]->get('foo'));
    $this->assertNull($stores[0]->get('bar'));
    $this->assertEmpty($stores[0]->getMultiple(['foo', 'bar']));
    // Verify that the item in the other collection still exists.
    $this->assertEquals($this->objects[5], $stores[1]->get('foo'));

    // Test that setWithExpireIfNotExists() succeeds only the first time.
    $key = $this->randomMachineName();
    for ($i = 0; $i <= 1; $i++) {
      // setWithExpireIfNotExists() should be TRUE the first time (when $i is
      // 0) and FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $stores[0]->setWithExpireIfNotExists($key, $this->objects[$i], rand(500, 100000)));
      $this->assertEquals($this->objects[0], $stores[0]->get($key));
      // Verify that the other collection is not affected.
      $this->assertNull($stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $stores[0]->delete($key);
    $stores[0]->setWithExpireIfNotExists($key, $this->objects[1], rand(500, 100000));
    // This time it should succeed.
    $this->assertEquals($this->objects[1], $stores[0]->get($key));
    // Verify that the other collection is still not affected.
    $this->assertNull($stores[1]->get($key));

  }

  /**
   * Tests data expiration.
   */
  public function testExpiration() {
    $stores = $this->createStorage();
    $day = 604800;

    // Set an item to expire in the past and another without an expiration.
    $stores[0]->setWithExpire('yesterday', 'all my troubles seemed so far away', -1 * $day);
    $stores[0]->set('troubles', 'here to stay');

    // Only the non-expired item should be returned.
    $this->assertFalse($stores[0]->has('yesterday'));
    $this->assertNull($stores[0]->get('yesterday'));
    $this->assertTrue($stores[0]->has('troubles'));
    $this->assertIdentical($stores[0]->get('troubles'), 'here to stay');
    $this->assertCount(1, $stores[0]->getMultiple(['yesterday', 'troubles']));

    // Store items set to expire in the past in various ways.
    $stores[0]->setWithExpire($this->randomMachineName(), $this->objects[0], -7 * $day);
    $stores[0]->setWithExpireIfNotExists($this->randomMachineName(), $this->objects[1], -5 * $day);
    $stores[0]->setMultipleWithExpire(
      [
        $this->randomMachineName() => $this->objects[2],
        $this->randomMachineName() => $this->objects[3],
      ],
      -3 * $day
    );
    $stores[0]->setWithExpireIfNotExists('yesterday', "you'd forgiven me", -1 * $day);
    $stores[0]->setWithExpire('still', "'til we say we're sorry", 2 * $day);

    // Ensure only non-expired items are retrieved.
    $all = $stores[0]->getAll();
    $this->assertCount(2, $all);
    foreach (['troubles', 'still'] as $key) {
      $this->assertTrue(!empty($all[$key]));
    }

    // Test DatabaseStorageExpirable::setWithExpireIfNotExists() will overwrite
    // expired items.
    $this->assertNull($stores[0]->get('yesterday'));
    $stores[0]->setWithExpireIfNotExists('yesterday', 'Oh, yesterday came suddenly', $day);
    $this->assertSame('Oh, yesterday came suddenly', $stores[0]->get('yesterday'));
  }

}
