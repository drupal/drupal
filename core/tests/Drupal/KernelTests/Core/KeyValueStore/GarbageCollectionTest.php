<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\KeyValueStore;

use Drupal\Component\Serialization\PhpSerialize;
use Drupal\Core\Database\Database;
use Drupal\Core\KeyValueStore\DatabaseStorageExpirable;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Hook\SystemHooks;

/**
 * Tests garbage collection for the expirable key-value database storage.
 *
 * @group KeyValueStore
 */
class GarbageCollectionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests garbage collection.
   */
  public function testGarbageCollection(): void {
    $collection = $this->randomMachineName();
    $connection = Database::getConnection();
    $store = new DatabaseStorageExpirable($collection, new PhpSerialize(), $connection, \Drupal::time());

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
          'expire' => \Drupal::time()->getRequestTime() - 1,
        ])
        ->execute();
    }

    // Perform a new set operation and then trigger garbage collection.
    $store->setWithExpire('autumn', 'winter', rand(500, 1000000));
    $systemCron = new SystemHooks();
    $systemCron->cron();

    // Query the database and confirm that the stale records were deleted.
    $result = $connection->select('key_value_expire', 'kvp')
      ->fields('kvp', ['name'])
      ->condition('collection', $collection)
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $result, 'Only one item remains after garbage collection');

  }

}
