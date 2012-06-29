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
    $this->storage = new DatabaseStorage();
    $this->invalidStorage = new DatabaseStorage(array('connection' => 'invalid'));
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
