<?php

/**
 * @file
 * Definition of Drupal\config\Tests\Storage\ConfigStorageTestBase.
 */

namespace Drupal\config\Tests\Storage;

use Drupal\simpletest\WebTestBase;

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
abstract class ConfigStorageTestBase extends WebTestBase {

  /**
   * Tests storage controller CRUD operations.
   *
   * @todo Coverage: Trigger PDOExceptions / Database exceptions.
   * @todo Coverage: Trigger Yaml's ParseException and DumpException.
   */
  function testCRUD() {
    $name = 'config_test.storage';

    // Reading a non-existing name returns an empty data array.
    $data = $this->storage->read($name);
    $this->assertIdentical($data, array());

    // Reading a name containing non-decodeable data returns an empty array.
    $this->insert($name, '');
    $data = $this->storage->read($name);
    $this->assertIdentical($data, array());

    $this->update($name, 'foo');
    $data = $this->storage->read($name);
    $this->assertIdentical($data, array());

    $this->delete($name);

    // Writing data returns TRUE and the data has been written.
    $data = array('foo' => 'bar');
    $result = $this->storage->write($name, $data);
    $this->assertIdentical($result, TRUE);
    $raw_data = $this->read($name);
    $this->assertIdentical($raw_data, $data);

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

    // Deleting an existing name returns TRUE.
    $result = $this->storage->delete($name);
    $this->assertIdentical($result, TRUE);

    // Deleting a non-existing name returns FALSE.
    $result = $this->storage->delete($name);
    $this->assertIdentical($result, FALSE);

    // Reading from a non-existing storage bin returns an empty data array.
    $data = $this->invalidStorage->read($name);
    $this->assertIdentical($data, array());

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
  }

  abstract protected function read($name);

  abstract protected function insert($name, $data);

  abstract protected function update($name, $data);

  abstract protected function delete($name);
}
