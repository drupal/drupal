<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\MemoryStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;
use Drupal\Core\KeyValueStore\KeyValueFactory;

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
    $parameter = array();
    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.memory';
    $this->container->setParameter('factory.keyvalue', $parameter);
  }

}
