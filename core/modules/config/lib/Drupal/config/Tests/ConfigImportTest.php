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
  public static function getInfo() {
    return array(
      'name' => 'Import configuration',
      'description' => 'Tests importing configuration from files into active store.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp(array('config_test'));
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
  }
}
