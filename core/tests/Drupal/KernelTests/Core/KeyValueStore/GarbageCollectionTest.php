<?php

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Component\Serialization\PhpSerialize;
use Drupal\Core\Database\Database;
use Drupal\Core\KeyValueStore\DatabaseStorageExpirable;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests garbage collection for the expirable key-value database storage.
 *
 * @group KeyValueStore
 */
class GarbageCollectionTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system'];

  /**
   * Tests garbage collection.
   */
  public function testGarbageCollection() {
    $collection = $this->randomMachineName();
    $connection = Database::getConnection();
    $store = new DatabaseStorageExpirable($collection, new PhpSerialize(), $connection);

    // Insert some items and confirm that they're set.
    for ($i = 0; $i <= 3; $i++) {
      $store->setWithExpire('key_' . $i, $this->randomObject(), rand(500, 100000));
    }
    $this->assertCount(4, $store->getAll(), 'Four items were written to the storage.');

    // Manually expire the data.
    for ($i = 0; $i <= 3; $i++) {
      $connection->merge('key_value_expire')
        ->keys([
            'name' => 'key_' . $i,
            'collection' => $collection,
          ])
        ->fields([
            'expire' => REQUEST_TIME - 1,
          ])
        ->execute();
    }

    // Perform a new set operation and then trigger garbage collection.
    $store->setWithExpire('autumn', 'winter', rand(500, 1000000));
    system_cron();

    // Query the database and confirm that the stale records were deleted.
    $result = $connection->select('key_value_expire', 'kvp')
      ->fields('kvp', ['name'])
      ->condition('collection', $collection)
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $result, 'Only one item remains after garbage collection');

  }

}
