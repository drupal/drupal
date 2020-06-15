<?php

namespace Drupal\KernelTests\Core\TempStore;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Lock\DatabaseLockBackend;
use Drupal\Core\Database\Database;

/**
 * Tests the temporary object storage system.
 *
 * @group TempStore
 * @see \Drupal\Core\TempStore\SharedTempStore
 */
class TempStoreDatabaseTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  /**
   * A key/value store factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $storeFactory;

  /**
   * The name of the key/value collection to set and retrieve.
   *
   * @var string
   */
  protected $collection;

  /**
   * An array of random stdClass objects.
   *
   * @var array
   */
  protected $objects = [];

  protected function setUp() {
    parent::setUp();

    // Install system tables to test the key/value storage without installing a
    // full Drupal environment.
    $this->installSchema('system', ['key_value_expire']);

    // Create several objects for testing.
    for ($i = 0; $i <= 3; $i++) {
      $this->objects[$i] = $this->randomObject();
    }

  }

  /**
   * Tests the SharedTempStore API.
   */
  public function testSharedTempStore() {
    // Create a key/value collection.
    $database = Database::getConnection();
    $factory = new SharedTempStoreFactory(new KeyValueExpirableFactory(\Drupal::getContainer()), new DatabaseLockBackend($database), $this->container->get('request_stack'));
    $collection = $this->randomMachineName();

    // Create two mock users.
    for ($i = 0; $i <= 1; $i++) {
      $users[$i] = mt_rand(500, 5000000);

      // Storing the SharedTempStore objects in a class member variable causes a
      // fatal exception, because in that situation garbage collection is not
      // triggered until the test class itself is destructed, after tearDown()
      // has deleted the database tables. Store the objects locally instead.
      /** @var \Drupal\Core\TempStore\SharedTempStore[] $stores */
      $stores[$i] = $factory->get($collection, $users[$i]);
    }

    $key = $this->randomMachineName();
    // Test that setIfNotExists() succeeds only the first time.
    for ($i = 0; $i <= 1; $i++) {
      // setIfNotExists() should be TRUE the first time (when $i is 0) and
      // FALSE the second time (when $i is 1).
      $this->assertEqual(!$i, $stores[0]->setIfNotExists($key, $this->objects[$i]));
      $metadata = $stores[0]->getMetadata($key);
      $this->assertEqual($users[0], $metadata->getOwnerId());
      $this->assertEquals($this->objects[0], $stores[0]->get($key));
      // Another user should get the same result.
      $metadata = $stores[1]->getMetadata($key);
      $this->assertEqual($users[0], $metadata->getOwnerId());
      $this->assertEquals($this->objects[0], $stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $stores[0]->delete($key);
    $stores[0]->setIfNotExists($key, $this->objects[1]);
    // This time it should succeed.
    $this->assertEquals($this->objects[1], $stores[0]->get($key));

    // This user can update the object.
    $stores[0]->set($key, $this->objects[2]);
    $this->assertEquals($this->objects[2], $stores[0]->get($key));
    // The object is the same when another user loads it.
    $this->assertEquals($this->objects[2], $stores[1]->get($key));

    // This user should be allowed to get, update, delete.
    $this->assertInstanceOf(\stdClass::class, $stores[0]->getIfOwner($key));
    $this->assertTrue($stores[0]->setIfOwner($key, $this->objects[1]));
    $this->assertTrue($stores[0]->deleteIfOwner($key));

    // Another user can update the object and become the owner.
    $stores[1]->set($key, $this->objects[3]);
    $this->assertEquals($this->objects[3], $stores[0]->get($key));
    $this->assertEquals($this->objects[3], $stores[1]->get($key));
    $metadata = $stores[1]->getMetadata($key);
    $this->assertEqual($users[1], $metadata->getOwnerId());

    // The first user should be informed that the second now owns the data.
    $metadata = $stores[0]->getMetadata($key);
    $this->assertEqual($users[1], $metadata->getOwnerId());

    // The first user should no longer be allowed to get, update, delete.
    $this->assertNull($stores[0]->getIfOwner($key));
    $this->assertFalse($stores[0]->setIfOwner($key, $this->objects[1]));
    $this->assertFalse($stores[0]->deleteIfOwner($key));

    // Now manually expire the item (this is not exposed by the API) and then
    // assert it is no longer accessible.
    $database->update('key_value_expire')
      ->fields(['expire' => REQUEST_TIME - 1])
      ->condition('collection', "tempstore.shared.$collection")
      ->condition('name', $key)
      ->execute();
    $this->assertNull($stores[0]->get($key));
    $this->assertNull($stores[1]->get($key));
  }

}
