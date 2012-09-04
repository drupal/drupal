<?php

/**
 * @file
 * Definition of Drupal\config\Tests\Storage\DatabaseStorageTest.
 */

namespace Drupal\config\Tests\Storage;

use Drupal\Core\Config\DatabaseStorage;

/**
 * Tests DatabaseStorage controller operations.
 */
class DatabaseStorageTest extends ConfigStorageTestBase {
  public static function getInfo() {
    return array(
      'name' => 'DatabaseStorage controller operations',
      'description' => 'Tests DatabaseStorage controller operations.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    $schema['config'] = array(
      'description' => 'Default active store for the configuration system.',
      'fields' => array(
        'name' => array(
          'description' => 'The identifier for the configuration entry, such as module.example (the name of the file, minus the file extension).',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'data' => array(
          'description' => 'The raw data for this configuration entry.',
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'translatable' => TRUE,
        ),
      ),
      'primary key' => array('name'),
    );
    db_create_table('config', $schema['config']);

    $this->storage = new DatabaseStorage($this->container->get('database'), 'config');
    $this->invalidStorage = new DatabaseStorage($this->container->get('database'), 'invalid');

    // ::listAll() verifications require other configuration data to exist.
    $this->storage->write('system.performance', array());
  }

  protected function read($name) {
    $data = db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $name))->fetchField();
    return unserialize($data);
  }

  protected function insert($name, $data) {
    db_insert('config')->fields(array('name' => $name, 'data' => $data))->execute();
  }

  protected function update($name, $data) {
    db_update('config')->fields(array('data' => $data))->condition('name', $name)->execute();
  }

  protected function delete($name) {
    db_delete('config')->condition('name', $name)->execute();
  }
}
