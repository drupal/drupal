<?php

namespace Drupal\KernelTests\Core\TempStore;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\Session\AccountProxyInterface;
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
   * Tests the SharedTempStore API.
   */
  public function testSharedTempStore() {
    // Create testing objects.
    $objects = [];
    for ($i = 0; $i <= 3; $i++) {
      $objects[$i] = $this->randomObject();
    }

    // Create a key/value collection.
    $database = Database::getConnection();
    // Mock the current user service so that isAnonymous returns FALSE.
    $current_user = $this->prophesize(AccountProxyInterface::class);
    $factory = new SharedTempStoreFactory(
      new KeyValueExpirableFactory(\Drupal::getContainer()),
      new DatabaseLockBackend($database),
      $this->container->get('request_stack'),
      $current_user->reveal()
    );
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
      $this->assertEquals(!$i, $stores[0]->setIfNotExists($key, $objects[$i]));
      $metadata = $stores[0]->getMetadata($key);
      $this->assertEquals($users[0], $metadata->getOwnerId());
      $this->assertEquals($objects[0], $stores[0]->get($key));
      // Another user should get the same result.
      $metadata = $stores[1]->getMetadata($key);
      $this->assertEquals($users[0], $metadata->getOwnerId());
      $this->assertEquals($objects[0], $stores[1]->get($key));
    }

    // Remove the item and try to set it again.
    $stores[0]->delete($key);
    $stores[0]->setIfNotExists($key, $objects[1]);
    // This time it should succeed.
    $this->assertEquals($objects[1], $stores[0]->get($key));

    // This user can update the object.
    $stores[0]->set($key, $objects[2]);
    $this->assertEquals($objects[2], $stores[0]->get($key));
    // The object is the same when another user loads it.
    $this->assertEquals($objects[2], $stores[1]->get($key));

    // This user should be allowed to get, update, delete.
    $this->assertInstanceOf(\stdClass::class, $stores[0]->getIfOwner($key));
    $this->assertTrue($stores[0]->setIfOwner($key, $objects[1]));
    $this->assertTrue($stores[0]->deleteIfOwner($key));

    // Another user can update the object and become the owner.
    $stores[1]->set($key, $objects[3]);
    $this->assertEquals($objects[3], $stores[0]->get($key));
    $this->assertEquals($objects[3], $stores[1]->get($key));
    $metadata = $stores[1]->getMetadata($key);
    $this->assertEquals($users[1], $metadata->getOwnerId());

    // The first user should be informed that the second now owns the data.
    $metadata = $stores[0]->getMetadata($key);
    $this->assertEquals($users[1], $metadata->getOwnerId());

    // The first user should no longer be allowed to get, update, delete.
    $this->assertNull($stores[0]->getIfOwner($key));
    $this->assertFalse($stores[0]->setIfOwner($key, $objects[1]));
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
