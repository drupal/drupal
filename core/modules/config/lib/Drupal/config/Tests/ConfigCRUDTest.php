<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigCRUDTest.
 */

namespace Drupal\config\Tests;

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
    $storage = $this->container->get('config.storage');
    $name = 'config_test.crud';

    $config = config($name);
    $this->assertIdentical($config->isNew(), TRUE);

    // Create a new configuration object.
    $config->set('value', 'initial');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the saved value.
    $actual_data = $storage->read($name);
    $this->assertIdentical($actual_data, array('value' => 'initial'));

    // Update the configuration object instance.
    $config->set('value', 'instance-update');
    $config->save();
    $this->assertIdentical($config->isNew(), FALSE);

    // Verify the active configuration contains the updated value.
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

    // Verify the active configuration contains no value.
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

    // Verify the active configuration contains the updated value.
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

}
