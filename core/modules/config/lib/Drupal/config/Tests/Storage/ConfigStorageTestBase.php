<?php

/**
 * @file
 * Definition of Drupal\config\Tests\Storage\ConfigStorageTestBase.
 */

namespace Drupal\config\Tests\Storage;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Base class for testing storage controller operations.
 *
 * All configuration storage controllers are expected to behave identically in
 * terms of reading, writing, listing, deleting, as well as error handling.
 *
 * Therefore, storage controller tests use a uncommon test case class structure;
 * the base class defines the test method(s) to execute, which are identical for
 * all storage controllers. The storage controller specific test case classes
 * supply the necessary helper methods to interact with the raw/native storage
 * directly.
 */
abstract class ConfigStorageTestBase extends DrupalUnitTestBase {

  /**
   * Tests storage controller CRUD operations.
   *
   * @todo Coverage: Trigger PDOExceptions / Database exceptions.
   * @todo Coverage: Trigger Yaml's ParseException and DumpException.
   */
  function testCRUD() {
    $name = 'config_test.storage';

    // Checking whether a non-existing name exists returns FALSE.
    $this->assertIdentical($this->storage->exists($name), FALSE);

    // Reading a non-existing name returns FALSE.
    $data = $this->storage->read($name);
    $this->assertIdentical($data, FALSE);

    // Reading a name containing non-decodeable data returns FALSE.
    $this->insert($name, '');
    $data = $this->storage->read($name);
    $this->assertIdentical($data, FALSE);

    $this->update($name, 'foo');
    $data = $this->storage->read($name);
    $this->assertIdentical($data, FALSE);

    $this->delete($name);

    // Writing data returns TRUE and the data has been written.
    $data = array('foo' => 'bar');
    $result = $this->storage->write($name, $data);
    $this->assertIdentical($result, TRUE);

    $raw_data = $this->read($name);
    $this->assertIdentical($raw_data, $data);

    // Checking whether an existing name exists returns TRUE.
    $this->assertIdentical($this->storage->exists($name), TRUE);

    // Writing the identical data again still returns TRUE.
    $result = $this->storage->write($name, $data);
    $this->assertIdentical($result, TRUE);

    // Listing all names returns all.
    $names = $this->storage->listAll();
    $this->assertTrue(in_array('system.performance', $names));
    $this->assertTrue(in_array($name, $names));

    // Listing all names with prefix returns names with that prefix only.
    $names = $this->storage->listAll('config_test.');
    $this->assertFalse(in_array('system.performance', $names));
    $this->assertTrue(in_array($name, $names));

    // Rename the configuration storage object.
    $new_name = 'config_test.storage_rename';
    $this->storage->rename($name, $new_name);
    $raw_data = $this->read($new_name);
    $this->assertIdentical($raw_data, $data);
    // Rename it back so further tests work.
    $this->storage->rename($new_name, $name);

    // Deleting an existing name returns TRUE.
    $result = $this->storage->delete($name);
    $this->assertIdentical($result, TRUE);

    // Deleting a non-existing name returns FALSE.
    $result = $this->storage->delete($name);
    $this->assertIdentical($result, FALSE);

    // Reading from a non-existing storage bin returns FALSE.
    $result = $this->invalidStorage->read($name);
    $this->assertIdentical($result, FALSE);

    // Deleting all names with prefix deletes the appropriate data and returns
    // TRUE.
    $files = array(
      'config_test.test.biff',
      'config_test.test.bang',
      'config_test.test.pow',
    );
    foreach ($files as $name) {
      $this->storage->write($name, $data);
    }

    $result = $this->storage->deleteAll('config_test.');
    $names = $this->storage->listAll('config_test.');
    $this->assertIdentical($result, TRUE);
    $this->assertIdentical($names, array());

    // Writing to a non-existing storage bin throws an exception.
    try {
      $this->invalidStorage->write($name, array('foo' => 'bar'));
      $this->fail('Exception not thrown upon writing to a non-existing storage bin.');
    }
    catch (\Exception $e) {
      $class = get_class($e);
      $this->pass($class . ' thrown upon writing to a non-existing storage bin.');
    }

    // Deleting from a non-existing storage bin throws an exception.
    try {
      $this->invalidStorage->delete($name);
      $this->fail('Exception not thrown upon deleting from a non-existing storage bin.');
    }
    catch (\Exception $e) {
      $class = get_class($e);
      $this->pass($class . ' thrown upon deleting from a non-existing storage bin.');
    }

    // Listing on a non-existing storage bin throws an exception.
    try {
      $this->invalidStorage->listAll();
      $this->fail('Exception not thrown upon listing from a non-existing storage bin.');
    }
    catch (\Exception $e) {
      $class = get_class($e);
      $this->pass($class . ' thrown upon listing from a non-existing storage bin.');
    }

    // Test renaming an object that does not exist throws an exception.
    try {
      $this->storage->rename('config_test.storage_does_not_exist', 'config_test.storage_does_not_exist_rename');
    }
    catch (\Exception $e) {
      $class = get_class($e);
      $this->pass($class . ' thrown upon renaming a nonexistent storage bin.');
    }

    // Test renaming to an object that already exists throws an exception.
    try {
      $this->storage->rename('system.cron', 'system.performance');
    }
    catch (\Exception $e) {
      $class = get_class($e);
      $this->pass($class . ' thrown upon renaming a nonexistent storage bin.');
    }

  }

  abstract protected function read($name);

  abstract protected function insert($name, $data);

  abstract protected function update($name, $data);

  abstract protected function delete($name);
}
