<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\MemoryStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

/**
 * Tests the key-value memory storage.
 *
 * @group KeyValueStore
 */
class MemoryStorageTest extends StorageTestBase {

  /**
   * Holds the original default key/value service name.
   *
   * @var String
   */
  protected $originalKeyValue = NULL;

  protected function setUp() {
    parent::setUp();
    $this->container
      ->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory');
    $this->settingsSet('keyvalue_default', 'keyvalue.memory');
  }

}
