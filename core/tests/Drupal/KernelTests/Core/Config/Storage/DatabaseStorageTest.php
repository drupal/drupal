<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;

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
  }

  protected function read($name) {
    $data = Database::getConnection()->select('config', 'c')->fields('c', ['data'])->condition('name', $name)->execute()->fetchField();
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

  /**
   * Tests that operations throw exceptions if the query fails.
   */
  public function testExceptionIsThrownIfQueryFails(): void {
    $connection = Database::getConnection();
    if ($connection->databaseType() === 'sqlite') {
      // See: https://www.drupal.org/project/drupal/issues/3349286
      $this->markTestSkipped('SQLite cannot allow detection of exceptions due to double quoting.');
      return;
    }

    Database::getConnection()->schema()->dropTable('config');
    // In order to simulate database issue create a table with an incorrect
    // specification.
    $table_specification = [
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
      ],
    ];
    Database::getConnection()->schema()->createTable('config', $table_specification);

    try {
      $this->storage->exists('config.settings');
      $this->fail('Expected exception not thrown from exists()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->read('config.settings');
      $this->fail('Expected exception not thrown from read()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->readMultiple(['config.settings', 'config.settings2']);
      $this->fail('Expected exception not thrown from readMultiple()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->write('config.settings', ['data' => '']);
      $this->fail('Expected exception not thrown from deleteAll()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->listAll();
      $this->fail('Expected exception not thrown from listAll()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->deleteAll();
      $this->fail('Expected exception not thrown from deleteAll()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    try {
      $this->storage->getAllCollectionNames();
      $this->fail('Expected exception not thrown from getAllCollectionNames()');
    }
    catch (DatabaseExceptionWrapper $e) {
      // Exception was expected
    }

    $this->assertTrue(TRUE);
  }

}
