<?php

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Database\Database;

/**
 * Tests DatabaseStorage operations.
 *
 * @group config
 */
class DatabaseStorageTest extends ConfigStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = new DatabaseStorage($this->container->get('database'), 'config');
    $this->invalidStorage = new DatabaseStorage($this->container->get('database'), 'invalid');

    // ::listAll() verifications require other configuration data to exist.
    $this->storage->write('system.performance', []);
  }

  protected function read($name) {
    $data = Database::getConnection()->query('SELECT data FROM {config} WHERE name = :name', [':name' => $name])->fetchField();
    return unserialize($data);
  }

  protected function insert($name, $data) {
    Database::getConnection()->insert('config')->fields(['name' => $name, 'data' => $data])->execute();
  }

  protected function update($name, $data) {
    Database::getConnection()->update('config')->fields(['data' => $data])->condition('name', $name)->execute();
  }

  protected function delete($name) {
    Database::getConnection()->delete('config')->condition('name', $name)->execute();
  }

}
