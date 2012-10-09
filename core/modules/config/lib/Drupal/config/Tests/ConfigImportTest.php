<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigImportTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests importing configuration from files into active store.
 */
class ConfigImportTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Import configuration',
      'description' => 'Tests importing configuration from files into active store.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    // Clear out any possibly existing hook invocation records.
    unset($GLOBALS['hook_config_test']);
  }

  /**
   * Tests omission of module APIs for bare configuration operations.
   */
  function testNoImport() {
    $dynamic_name = 'config_test.dynamic.default';

    // Verify the default configuration values exist.
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'default');

    // Verify that a bare config() does not involve module APIs.
    $this->assertFalse(isset($GLOBALS['hook_config_test']));

    // Export.
    config_export();

    // Verify that config_export() does not involve module APIs.
    $this->assertFalse(isset($GLOBALS['hook_config_test']));
  }

  /**
   * Tests deletion of configuration during import.
   */
  function testDeleted() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the default configuration values exist.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'default');

    // Export.
    config_export();

    // Delete the configuration objects from the staging directory.
    $staging->delete($name);
    $staging->delete($dynamic_name);

    // Import.
    config_import();

    // Verify the values have disappeared.
    $this->assertIdentical($storage->read($name), FALSE);
    $this->assertIdentical($storage->read($dynamic_name), FALSE);

    $config = config($name);
    $this->assertIdentical($config->get('foo'), NULL);
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('id'), NULL);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse(config_sync_get_changes($staging, $storage));
  }

  /**
   * Tests creation of configuration during import.
   */
  function testNew() {
    $name = 'config_test.new';
    $dynamic_name = 'config_test.dynamic.new';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Export.
    config_export();

    // Verify the configuration to create does not exist yet.
    $this->assertIdentical($storage->exists($name), FALSE, $name . ' not found.');
    $this->assertIdentical($storage->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    $this->assertIdentical($staging->exists($name), FALSE, $name . ' not found.');
    $this->assertIdentical($staging->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    // Create new configuration objects in the staging directory.
    $original_name_data = array(
      'add_me' => 'new value',
    );
    $staging->write($name, $original_name_data);
    $original_dynamic_data = array(
      'id' => 'new',
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'label' => 'New',
      'style' => '',
      'langcode' => 'und',
    );
    $staging->write($dynamic_name, $original_dynamic_data);
    $this->assertIdentical($staging->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Import.
    config_import();

    // Verify the values appeared.
    $config = config($name);
    $this->assertIdentical($config->get('add_me'), $original_name_data['add_me']);
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('label'), $original_dynamic_data['label']);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse(config_sync_get_changes($staging, $storage));
  }

  /**
   * Tests updating of configuration during import.
   */
  function testUpdated() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Export.
    config_export();

    // Verify that the configuration objects to import exist.
    $this->assertIdentical($storage->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($storage->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    $this->assertIdentical($staging->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Replace the file content of the existing configuration objects in the
    // staging directory.
    $original_name_data = array(
      'foo' => 'beer',
    );
    $staging->write($name, $original_name_data);
    $original_dynamic_data = $staging->read($dynamic_name);
    $original_dynamic_data['label'] = 'Updated';
    $staging->write($dynamic_name, $original_dynamic_data);

    // Verify the active store still returns the default values.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'Default');

    // Import.
    config_import();

    // Verify the values were updated.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'beer');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'Updated');

    // Verify that the original file content is still the same.
    $this->assertIdentical($staging->read($name), $original_name_data);
    $this->assertIdentical($staging->read($dynamic_name), $original_dynamic_data);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse(config_sync_get_changes($staging, $storage));
  }

}
