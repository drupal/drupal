<?php

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for testing storage operations.
 *
 * All configuration storages are expected to behave identically in
 * terms of reading, writing, listing, deleting, as well as error handling.
 *
 * Therefore, storage tests use an uncommon test case class structure;
 * the base class defines the test method(s) to execute, which are identical
 * for all storages. The storage specific test case classes supply the
 * necessary helper methods to interact with the raw/native storage
 * directly.
 */
abstract class ConfigStorageTestBase extends KernelTestBase {

  /**
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $invalidStorage;

  /**
   * Tests storage CRUD operations.
   *
   * @todo Coverage: Trigger PDOExceptions / Database exceptions.
   */
  public function testCRUD() {
    $name = 'config_test.storage';

    // Checking whether a non-existing name exists returns FALSE.
    $this->assertFalse($this->storage->exists($name));

    // Reading a non-existing name returns FALSE.
    $data = $this->storage->read($name);
    $this->assertFalse($data);

    // Writing data returns TRUE and the data has been written.
    $data = ['foo' => 'bar'];
    $result = $this->storage->write($name, $data);
    $this->assertTrue($result);

    $raw_data = $this->read($name);
    $this->assertSame($data, $raw_data);

    // Checking whether an existing name exists returns TRUE.
    $this->assertTrue($this->storage->exists($name));

    // Writing the identical data again still returns TRUE.
    $result = $this->storage->write($name, $data);
    $this->assertTrue($result);

    // Listing all names returns all.
    $names = $this->storage->listAll();
    $this->assertContains('system.performance', $names);
    $this->assertContains($name, $names);

    // Listing all names with prefix returns names with that prefix only.
    $names = $this->storage->listAll('config_test.');
    $this->assertNotContains('system.performance', $names);
    $this->assertContains($name, $names);

    // Rename the configuration storage object.
    $new_name = 'config_test.storage_rename';
    $this->storage->rename($name, $new_name);
    $raw_data = $this->read($new_name);
    $this->assertSame($data, $raw_data);
    // Rename it back so further tests work.
    $this->storage->rename($new_name, $name);

    // Deleting an existing name returns TRUE.
    $result = $this->storage->delete($name);
    $this->assertTrue($result);

    // Deleting a non-existing name returns FALSE.
    $result = $this->storage->delete($name);
    $this->assertFalse($result);

    // Deleting all names with prefix deletes the appropriate data and returns
    // TRUE.
    $files = [
      'config_test.test.biff',
      'config_test.test.bang',
      'config_test.test.pow',
    ];
    foreach ($files as $name) {
      $this->storage->write($name, $data);
    }

    // Test that deleting a prefix that returns no configuration returns FALSE
    // because nothing is deleted.
    $this->assertFalse($this->storage->deleteAll('some_thing_that_cannot_exist'));

    $result = $this->storage->deleteAll('config_test.');
    $names = $this->storage->listAll('config_test.');
    $this->assertTrue($result);
    $this->assertSame([], $names);

    // Test renaming an object that does not exist returns FALSE.
    $this->assertFalse($this->storage->rename('config_test.storage_does_not_exist', 'config_test.storage_does_not_exist_rename'));

    // Test renaming to an object that already returns FALSE.
    $data = ['foo' => 'bar'];
    $this->assertTrue($this->storage->write($name, $data));
    $this->assertFalse($this->storage->rename('config_test.storage_does_not_exist', $name));
  }

  /**
   * Tests an invalid storage.
   */
  public function testInvalidStorage() {
    $name = 'config_test.storage';

    // Write something to the valid storage to prove that the storages do not
    // pollute one another.
    $data = ['foo' => 'bar'];
    $result = $this->storage->write($name, $data);
    $this->assertTrue($result);

    $raw_data = $this->read($name);
    $this->assertSame($data, $raw_data);

    // Reading from a non-existing storage bin returns FALSE.
    $result = $this->invalidStorage->read($name);
    $this->assertFalse($result);

    // Deleting from a non-existing storage bin throws an exception.
    try {
      $this->invalidStorage->delete($name);
      $this->fail('Exception not thrown upon deleting from a non-existing storage bin.');
    }
    catch (\Exception $e) {
      // An exception occurred as expected; just continue.
    }

    // Listing on a non-existing storage bin returns an empty array.
    $result = $this->invalidStorage->listAll();
    $this->assertSame([], $result);

    // Getting all collections on a non-existing storage bin return an empty
    // array.
    $this->assertSame([], $this->invalidStorage->getAllCollectionNames());

    // Writing to a non-existing storage bin creates the bin.
    $this->invalidStorage->write($name, ['foo' => 'bar']);
    $result = $this->invalidStorage->read($name);
    $this->assertSame(['foo' => 'bar'], $result);
  }

  /**
   * Tests storage writing and reading data preserving data type.
   */
  public function testDataTypes() {
    $name = 'config_test.types';
    $data = [
      'array' => [],
      'boolean' => TRUE,
      'exp' => 1.2e+34,
      'float' => 3.14159,
      'hex' => 0xC,
      'int' => 99,
      'octal' => 0775,
      'string' => 'string',
      'string_int' => '1',
    ];

    $result = $this->storage->write($name, $data);
    $this->assertTrue($result);

    $read_data = $this->storage->read($name);
    $this->assertSame($data, $read_data);
  }

  /**
   * Tests that the storage supports collections.
   */
  public function testCollection() {
    $name = 'config_test.storage';
    $data = ['foo' => 'bar'];
    $result = $this->storage->write($name, $data);
    $this->assertTrue($result);
    $this->assertSame($data, $this->storage->read($name));

    // Create configuration in a new collection.
    $new_storage = $this->storage->createCollection('collection.sub.new');
    $this->assertFalse($new_storage->exists($name));
    $this->assertEqual([], $new_storage->listAll());
    $this->assertFalse($new_storage->delete($name));
    $this->assertFalse($new_storage->deleteAll('config_test.'));
    $this->assertFalse($new_storage->deleteAll());
    $this->assertFalse($new_storage->rename($name, 'config_test.another_name'));
    $new_storage->write($name, $data);
    $this->assertTrue($result);
    $this->assertSame($data, $new_storage->read($name));
    $this->assertEqual([$name], $new_storage->listAll());
    $this->assertTrue($new_storage->exists($name));
    $new_data = ['foo' => 'baz'];
    $new_storage->write($name, $new_data);
    $this->assertTrue($result);
    $this->assertSame($new_data, $new_storage->read($name));

    // Create configuration in another collection.
    $another_storage = $this->storage->createCollection('collection.sub.another');
    $this->assertFalse($another_storage->exists($name));
    $this->assertEqual([], $another_storage->listAll());
    $another_storage->write($name, $new_data);
    $this->assertTrue($result);
    $this->assertSame($new_data, $another_storage->read($name));
    $this->assertEqual([$name], $another_storage->listAll());
    $this->assertTrue($another_storage->exists($name));

    // Create configuration in yet another collection.
    $alt_storage = $this->storage->createCollection('alternate');
    $alt_storage->write($name, $new_data);
    $this->assertTrue($result);
    $this->assertSame($new_data, $alt_storage->read($name));

    // Switch back to the collection-less mode and check the data still exists
    // add has not been touched.
    $this->assertSame($data, $this->storage->read($name));

    // Check that the getAllCollectionNames() method works.
    $this->assertSame(['alternate', 'collection.sub.another', 'collection.sub.new'], $this->storage->getAllCollectionNames());

    // Check that the collections are removed when they are empty.
    $alt_storage->delete($name);
    $this->assertSame(['collection.sub.another', 'collection.sub.new'], $this->storage->getAllCollectionNames());

    // Create configuration in collection called 'collection'. This ensures that
    // FileStorage's collection storage works regardless of its use of
    // subdirectories.
    $parent_storage = $this->storage->createCollection('collection');
    $this->assertFalse($parent_storage->exists($name));
    $this->assertEqual([], $parent_storage->listAll());
    $parent_storage->write($name, $new_data);
    $this->assertTrue($result);
    $this->assertSame($new_data, $parent_storage->read($name));
    $this->assertEqual([$name], $parent_storage->listAll());
    $this->assertTrue($parent_storage->exists($name));
    $this->assertSame(['collection', 'collection.sub.another', 'collection.sub.new'], $this->storage->getAllCollectionNames());
    $parent_storage->deleteAll();
    $this->assertSame(['collection.sub.another', 'collection.sub.new'], $this->storage->getAllCollectionNames());

    // Test operations on a collection emptied through deletion.
    $this->assertFalse($parent_storage->exists($name));
    $this->assertEqual([], $parent_storage->listAll());
    $this->assertFalse($parent_storage->delete($name));
    $this->assertFalse($parent_storage->deleteAll('config_test.'));
    $this->assertFalse($parent_storage->deleteAll());
    $this->assertFalse($parent_storage->rename($name, 'config_test.another_name'));

    // Check that the having an empty collection-less storage does not break
    // anything. Before deleting check that the previous delete did not affect
    // data in another collection.
    $this->assertSame($data, $this->storage->read($name));
    $this->storage->delete($name);
    $this->assertSame(['collection.sub.another', 'collection.sub.new'], $this->storage->getAllCollectionNames());
  }

  abstract protected function read($name);

  abstract protected function insert($name, $data);

  abstract protected function update($name, $data);

  abstract protected function delete($name);

}
