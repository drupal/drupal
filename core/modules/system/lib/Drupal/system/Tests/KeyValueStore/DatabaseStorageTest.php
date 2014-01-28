<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\DatabaseStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the key-value database storage.
 */
class DatabaseStorageTest extends StorageTestBase {

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
    $this->container
      ->register('database', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('default');
    $this->container
      ->register('keyvalue.database', 'Drupal\Core\KeyValueStore\KeyValueDatabaseFactory')
      ->addArgument(new Reference('database'));
    $this->settingsSet('keyvalue_default', 'keyvalue.database');
  }

  protected function tearDown() {
    db_drop_table('key_value');
    parent::tearDown();
  }

}
