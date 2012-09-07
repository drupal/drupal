<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigImportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests importing configuration from files into active store.
 */
class ConfigImportUITest extends WebTestBase {

  public static $modules = array('config', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Import/Export UI',
      'description' => 'Tests the user interface for importing/exporting configuration.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('synchronize configuration'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests exporting configuration.
   */
  function testExport() {
    $name = 'config_test.system';
    $dynamic_name = 'config_test.dynamic.default';

    // Verify the default configuration values exist.
    $config = config($name);
    $this->assertIdentical($config->get('foo'), 'bar');
    $config = config($dynamic_name);
    $this->assertIdentical($config->get('id'), 'default');

    // Verify that both appear as deleted by default.
    $this->drupalGet('admin/config/development/sync/export');
    $this->assertText($name);
    $this->assertText($dynamic_name);

    // Export and verify that both do not appear anymore.
    $this->drupalPost(NULL, array(), t('Export all'));
    $this->assertUrl('admin/config/development/sync/export');
    $this->assertNoText($name);
    $this->assertNoText($dynamic_name);

    // Verify that there are no further changes to export.
    $this->assertText(t('There are no configuration changes.'));

    // Verify that the import screen shows no changes either.
    $this->drupalGet('admin/config/development/sync');
    $this->assertText(t('There are no configuration changes.'));
  }

  /**
   * Tests importing configuration.
   */
  function testImport() {
    $name = 'config_test.new';
    $dynamic_name = 'config_test.dynamic.new';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    // Verify the configuration to create does not exist yet.
    $this->assertIdentical($storage->exists($name), FALSE, $name . ' not found.');
    $this->assertIdentical($storage->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');
    $this->assertIdentical($staging->exists($name), FALSE, $name . ' not found.');
    $this->assertIdentical($staging->exists($dynamic_name), FALSE, $dynamic_name . ' not found.');

    // Verify that the import UI does not allow to import without exported
    // configuration.
    $this->drupalGet('admin/config/development/sync');
    $this->assertText('There is no base configuration.');

    // Verify that the Export link yields to the export UI page, and export.
    $this->clickLink('Export');
    $this->drupalPost(NULL, array(), t('Export all'));

    // Create new configuration objects.
    $original_name_data = array(
      'add_me' => 'new value',
    );
    $staging->write($name, $original_name_data);
    $original_dynamic_data = array(
      'id' => 'new',
      'label' => 'New',
      'langcode' => 'und',
      'style' => '',
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
    );
    $staging->write($dynamic_name, $original_dynamic_data);
    $this->assertIdentical($staging->exists($name), TRUE, $name . ' found.');
    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Verify that both appear as new.
    $this->drupalGet('admin/config/development/sync');
    $this->assertText($name);
    $this->assertText($dynamic_name);

    // Import and verify that both do not appear anymore.
    $this->drupalPost(NULL, array(), t('Import all'));
    $this->assertUrl('admin/config/development/sync');
    $this->assertNoText($name);
    $this->assertNoText($dynamic_name);

    // Verify that there are no further changes to import.
    $this->assertText(t('There are no configuration changes.'));

    // Verify that the export screen shows no changes either.
    $this->drupalGet('admin/config/development/sync/export');
    $this->assertText(t('There are no configuration changes.'));
  }

  /**
   * Tests concurrent importing of configuration.
   */
  function testImportLock() {
    $name = 'config_test.new';
    $staging = $this->container->get('config.storage.staging');

    // Write a configuration object to import.
    $staging->write($name, array(
      'add_me' => 'new value',
    ));

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/sync');
    $this->assertNoText(t('There are no configuration changes.'));

    // Acquire a fake-lock on the import mechanism.
    $lock_name = 'config_import';
    lock_acquire($lock_name);

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPost(NULL, array(), t('Import all'));
    $this->assertUrl('admin/config/development/sync');
    $this->assertText(t('The import failed due to an error. Any errors have been logged.'));

    // Release the lock, just to keep testing sane.
    lock_release($lock_name);
  }
}
