<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\DatabaseStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

/**
 * Tests the key-value database storage.
 */
class DatabaseStorageTest extends StorageTestBase {

  /**
   * The name of the class to test.
   *
   * The tests themselves are in StorageTestBase and use this class.
   */
  protected $storageClass = 'Drupal\Core\KeyValueStore\DatabaseStorage';

  public static function getInfo() {
    return array(
      'name' => 'Database storage',
      'description' => 'Tests the key-value database storage.',
      'group' => 'Key-value store',
    );
  }

  protected function setUp() {
    parent::setUp();
    module_load_install('system');
    $schema = system_schema();
    db_create_table('key_value', $schema['key_value']);
  }

  protected function tearDown() {
    db_drop_table('key_value');
    parent::tearDown();
  }

}
