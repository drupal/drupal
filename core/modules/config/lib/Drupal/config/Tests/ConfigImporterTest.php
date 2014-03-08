<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigImporterTest.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests importing configuration from files into active configuration.
 */
class ConfigImporterTest extends DrupalUnitTestBase {

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'system');

  public static function getInfo() {
    return array(
      'name' => 'Import configuration',
      'description' => 'Tests importing configuration from files into active configuration.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    $this->installSchema('system', 'config_snapshot');

    $this->installConfig(array('config_test'));
    // Installing config_test's default configuration pollutes the global
    // variable being used for recording hook invocations by this test already,
    // so it has to be cleared out manually.
    unset($GLOBALS['hook_config_test']);

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.staging'),
      $this->container->get('config.storage')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed')
    );
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));
  }

  /**
   * Tests omission of module APIs for bare configuration operations.
   */
  function testNoImport() {
    $dynamic_name = 'config_test.dynamic.dotted.default';

    // Verify the default configuration values exist.
    $config = \Drupal::config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'dotted.default');

    // Verify that a bare \Drupal::config() does not involve module APIs.
    $this->assertFalse(isset($GLOBALS['hook_config_test']));
  }

  /**
   * Tests that trying to import from an empty staging configuration directory
   * fails.
   */
  function testEmptyImportFails() {
    try {
      $this->container->get('config.storage.staging')->deleteAll();
      $this->configImporter->reset()->import();
      $this->fail('ConfigImporterException thrown, successfully stopping an empty import.');
    }
    catch (ConfigImporterException $e) {
      $this->pass('ConfigImporterException thrown, successfully stopping an empty import.');
    }
  }

  /**
   * Tests verification of site UUID before importing configuration.
   */
  function testSiteUuidValidate() {
    $staging = \Drupal::service('config.storage.staging');
    // Create updated configuration object.
    $config_data = \Drupal::config('system.site')->get();
    // Generate a new site UUID.
    $config_data['uuid'] = \Drupal::service('uuid')->generate();
    $staging->write('system.site', $config_data);
    try {
      $this->configImporter->reset()->import();
      $this->assertFalse(FALSE, 'ConfigImporterException not thrown, invalid import was not stopped due to mis-matching site UUID.');
    }
    catch (ConfigImporterException $e) {
      $this->assertEqual($e->getMessage(), 'Site UUID in source storage does not match the target storage.');
    }
  }

  /**
   * Tests deletion of configuration during import.
   */
  function testDeleted() {
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the default configuration values exist.
    $config = \Drupal::config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'dotted.default');

    // Delete the file from the staging directory.
    $staging->delete($dynamic_name);

    // Import.
    $this->configImporter->reset()->import();

    // Verify the file has been removed.
    $this->assertIdentical($storage->read($dynamic_name), FALSE);

    $config = \Drupal::config($dynamic_name);
    $this->assertIdentical($config->get('id'), NULL);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertTrue(isset($GLOBALS['hook_config_test']['load']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->hasUnprocessedChanges());
  }

  /**
   * Tests creation of configuration during import.
   */
  function testNew() {
    $dynamic_name = 'config_test.dynamic.new';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the configuration to create does not exist yet.
    $this->assertIdentical($storage->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    // Create new config entity.
    $original_dynamic_data = array(
      'id' => 'new',
      'label' => 'New',
      'weight' => 0,
      'style' => '',
      'status' => TRUE,
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'langcode' => language_default()->id,
      'protected_property' => '',
    );
    $staging->write($dynamic_name, $original_dynamic_data);

    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values appeared.
    $config = \Drupal::config($dynamic_name);
    $this->assertIdentical($config->get('label'), $original_dynamic_data['label']);

    // Verify that appropriate module API hooks have been invoked.
    $this->assertFalse(isset($GLOBALS['hook_config_test']['load']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['presave']));
    $this->assertTrue(isset($GLOBALS['hook_config_test']['insert']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['update']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['predelete']));
    $this->assertFalse(isset($GLOBALS['hook_config_test']['delete']));

    // Verify that there is nothing more to import.
    $this->assertFalse($this->configImporter->hasUnprocessedChanges());
  }

  /**
   * Tests updating of configuration during import.
   */
  function testUpdated() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.dotted.default';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify that the configuration objects to import exist.
    $this->assertIdentical($storage->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($storage->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Replace the file content of the existing configuration objects in the
    // staging directory.
    $original_name_data = array(
      'foo' => 'beer',
    );
    $staging->write($name, $original_name_data);
    $original_dynamic_data = $storage->read($dynamic_name);
    $original_dynamic_data['label'] = 'Updated';
    $staging->write($dynamic_name, $original_dynamic_data);

    // Verify the active configuration still returns the default values.
    $config = \Drupal::config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = \Drupal::config($dynamic_name);
    $this->assertIdentical($config->get('label'), 'Default');

    // Import.
    $this->configImporter->reset()->import();

    // Verify the values were updated.
    \Drupal::configFactory()->reset($name);
    $config = \Drupal::config($name);
    $this->assertIdentical($config->get('foo'), 'beer');
    $config = \Drupal::config($dynamic_name);
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
    $this->assertFalse($this->configImporter->hasUnprocessedChanges());
  }
}

