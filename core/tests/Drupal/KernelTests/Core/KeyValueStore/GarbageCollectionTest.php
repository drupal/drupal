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
  public static $modules = ['system'];

  protected function setUp() {
    parent::setUp();

    // These additional tables are necessary due to the call to system_cron().
    $this->installSchema('system', ['key_value_expire']);
  }

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
    $this->assertIdentical(count($store->getAll()), 4, 'Four items were written to the storage.');

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
    $result = db_query(
      'SELECT name, value FROM {key_value_expire} WHERE collection = :collection',
      [
        ':collection' => $collection,
      ])->fetchAll();
    $this->assertIdentical(count($result), 1, 'Only one item remains after garbage collection');

  }

}
