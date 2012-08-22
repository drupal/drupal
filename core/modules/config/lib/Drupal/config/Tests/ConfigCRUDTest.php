<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigCRUDTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests CRUD operations on configuration objects.
 */
class ConfigCRUDTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'CRUD operations',
      'description' => 'Tests CRUD operations on configuration objects.',
      'group' => 'Configuration',
    );
  }

  /**
   * Tests CRUD operations.
   */
  function testCRUD() {
    $storage = new DatabaseStorage();
    $name = 'config_test.crud';

    $config = config($name);
    $this->assertIdentical($config->isNew(), TRUE);

    // Create a new configuration object.
    $config->set('value', 'initial');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active store contains the saved value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 'initial'));

    // Update the configuration object instance.
    $config->set('value', 'instance-update');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active store contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 'instance-update'));

    // Verify a call to config() immediately returns the updated value.
    $new_config = config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), FALSE);

    // Delete the configuration object.
    $config->delete();

    // Verify the configuration object is empty.
    $this->assertIdentical($config->get(), array());
    $this->assertIdentical($config->isNew(), TRUE);

    // Verify the active store contains no value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, FALSE);

    // Verify config() returns no data.
    $new_config = config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), TRUE);

    // Re-create the configuration object.
    $config->set('value', 're-created');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active store contains the updated value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 're-created'));

    // Verify a call to config() immediately returns the updated value.
    $new_config = config($name);
    $this->assertIdentical($new_config->get(), $config->get());
    $this->assertIdentical($config->isNew(), FALSE);

    // Rename the configuration object.
    $new_name = 'config_test.crud_rename';
    $config->rename($new_name);
    $renamed_config = config($new_name);
    $this->assertIdentical($renamed_config->get(), $config->get());
    $this->assertIdentical($renamed_config->isNew(), FALSE);
  }

  /**
   * Tests Drupal\Core\Config\Config::sortByKey().
   */
  function testDataKeySort() {
    $config = config('config_test.keysort');
    $config->set('new', 'Value to be replaced');
    $config->set('static', 'static');
    $config->save();
    // Clone this Config, so this test does not rely on any particular
    // architecture.
    $config = clone $config;

    // Load the configuration data into a new object.
    $new_config = config('config_test.keysort');
    // Clear the 'new' key that came first.
    $new_config->clear('new');
    // Add a new 'new' key and save.
    $new_config->set('new', 'Value to be replaced');
    $new_config->save();

    // Verify that the data of both objects is in the identical order.
    // assertIdentical() is the required essence of this test; it performs a
    // strict comparison, which means that keys and values must be identical and
    // their order must be identical.
    $this->assertIdentical($new_config->get(), $config->get());
  }
}
