<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigImportTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
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

    // Verify the default configuration values exist.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'default');

    // Export.
    config_export();

    // Delete the configuration objects.
    $file_storage = new FileStorage();
    $file_storage->delete($name);
    $file_storage->delete($dynamic_name);

    // Import.
    config_import();

    // Verify the values have disappeared.
    $database_storage = new DatabaseStorage();
    $this->assertIdentical($database_storage->read($name), FALSE);
    $this->assertIdentical($database_storage->read($dynamic_name), FALSE);

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
  }

  /**
   * Tests creation of configuration during import.
   */
  function testNew() {
    $name = 'config_test.new';
    $dynamic_name = 'config_test.dynamic.new';

    // Verify the configuration to create does not exist yet.
    $file_storage = new FileStorage();
    $this->assertIdentical($file_storage->exists($name), FALSE, $name . ' not found.');
    $this->assertIdentical($file_storage->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    // Export.
    config_export();

    // Create new configuration objects.
    $file_storage->write($name, array(
      'add_me' => 'new value',
    ));
    $file_storage->write($dynamic_name, array(
      'id' => 'new',
      'label' => 'New',
    ));
    $this->assertIdentical($file_storage->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($file_storage->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Import.
    config_import();

    // Verify the values appeared.
    $config = config($name);
    $this->assertIdentical($config->get('add_me'), 'new value');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'New');

    // Verify that appropriate module API hooks have been invoked.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));
  }

  /**
   * Tests updating of configuration during import.
   */
  function testUpdated() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.default';

    // Export.
    config_export();

    // Replace the file content of the existing configuration objects.
    $file_storage = new FileStorage();
    $this->assertIdentical($file_storage->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($file_storage->exists($dynamic_name), TRUE, $dynamic_name . ' found.');
    $file_storage->write($name, array(
      'foo' => 'beer',
    ));
    $file_storage->write($dynamic_name, array(
      'id' => 'default',
      'label' => 'Updated',
    ));

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

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));
  }

}
