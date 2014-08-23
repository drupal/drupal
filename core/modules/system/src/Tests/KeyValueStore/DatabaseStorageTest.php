<?php

/**
 * @file
 * Contains Drupal\system\Tests\KeyValueStore\DatabaseStorageTest.
 */

namespace Drupal\system\Tests\KeyValueStore;

use Drupal\Core\KeyValueStore\KeyValueFactory;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the key-value database storage.
 *
 * @group KeyValueStore
 */
class DatabaseStorageTest extends StorageTestBase {

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
      ->addArgument(new Reference('serialization.phpserialize'))
      ->addArgument(new Reference('database'));
    $this->container
      ->register('serialization.phpserialize', 'Drupal\Component\Serialization\PhpSerialize');
    $parameter = array();
    $parameter[KeyValueFactory::DEFAULT_SETTING] = 'keyvalue.database';
    $this->container->setParameter('factory.keyvalue', $parameter);
  }

  protected function tearDown() {
    db_drop_table('key_value');
    parent::tearDown();
  }

}
