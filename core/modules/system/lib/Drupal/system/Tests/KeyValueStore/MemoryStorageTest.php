<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\MemoryStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

/**
 * Tests the key-value memory storage.
 */
class MemoryStorageTest extends StorageTestBase {

  /**
   * The name of the class to test.
   *
   * The tests themselves are in StorageTestBase and use this class.
   */
  protected $storageClass = 'Drupal\Core\KeyValueStore\MemoryStorage';

  public static function getInfo() {
    return array(
      'name' => 'Memory storage',
      'description' => 'Tests the key-value memory storage.',
      'group' => 'Key-value store',
    );
  }

}
