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
      'name' => 'Import UI',
      'description' => 'Tests the user interface for importing/exporting configuration.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('synchronize configuration'));
    $this->drupalLogin($this->web_user);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));
  }

  /**
   * Tests importing configuration.
   */
  function testImport() {
    $name = 'system.site';
    $dynamic_name = 'config_test.dynamic.new';
    $storage = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');

    $this->drupalGet('admin/config/development/sync');
    $this->assertText('There are no configuration changes.');
    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    $this->assertIdentical($staging->exists($name), TRUE, $name . ' found.');

    // Create new config entity.
    $original_dynamic_data = array(
      'id' => 'new',
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'label' => 'New',
      'weight' => '0',
      'style' => '',
      'status' => '1',
      'langcode' => language_default()->id,
      'protected_property' => '',
    );
    $staging->write($dynamic_name, $original_dynamic_data);
    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/sync');
    $this->assertText($name);
    $this->assertText($dynamic_name);
    $this->assertFieldById('edit-submit', t('Import all'));

    // Import and verify that both do not appear anymore.
    $this->drupalPost(NULL, array(), t('Import all'));
    $this->assertNoText($name);
    $this->assertNoText($dynamic_name);
    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Verify that there are no further changes to import.
    $this->assertText(t('There are no configuration changes.'));

    // Verify site name has changed.
    $this->assertIdentical($new_site_name, config('system.site')->get('name'));

    // Verify that new config entity exists.
    $this->assertIdentical($original_dynamic_data, config($dynamic_name)->get());

    // Verify the cache got cleared.
    $this->assertTrue(isset($GLOBALS['hook_cache_flush']));
  }

  /**
   * Tests concurrent importing of configuration.
   */
  function testImportLock() {
    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/sync');
    $this->assertNoText(t('There are no configuration changes.'));

    // Acquire a fake-lock on the import mechanism.
    $config_importer_lock = $this->configImporter()->getId();
    $this->container->get('lock')->acquire($config_importer_lock);

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPost(NULL, array(), t('Import all'));
    $this->assertText(t('Another request may be synchronizing configuration already.'));

    // Release the lock, just to keep testing sane.
    $this->container->get('lock')->release($config_importer_lock);

    // Verify site name has not changed.
    $this->assertNotEqual($new_site_name, config('system.site')->get('name'));
  }

  /**
   * Tests the screen that shows differences between active and staging.
   */
  function testImportDiff() {
    $active = $this->container->get('config.storage');
    $staging = $this->container->get('config.storage.staging');
    $config_name = 'config_test.system';
    $change_key = 'foo';
    $remove_key = '404';
    $add_key = 'biff';
    $add_data = 'bangpow';
    $change_data = 'foobar';
    $original_data = array(
      'foo' => 'bar',
      '404' => 'herp',
    );

    // Change a configuration value in staging.
    $staging_data = $original_data;
    $staging_data[$change_key] = $change_data;
    $staging_data[$add_key] = $add_data;
    $staging->write($config_name, $staging_data);

    // Load the diff UI and verify that the diff reflects the change.
    $this->drupalGet('admin/config/development/sync/diff/' . $config_name);
    $this->assertTitle(format_string('View changes of @config_name | Drupal', array('@config_name' => $config_name)));

    // Reset data back to original, and remove a key
    $staging_data = $original_data;
    unset($staging_data[$remove_key]);
    $staging->write($config_name, $staging_data);

    // Load the diff UI and verify that the diff reflects a removed key.
    $this->drupalGet('admin/config/development/sync/diff/' . $config_name);

    // Reset data back to original and add a key
    $staging_data = $original_data;
    $staging_data[$add_key] = $add_data;
    $staging->write($config_name, $staging_data);

    // Load the diff UI and verify that the diff reflects an added key.
    $this->drupalGet('admin/config/development/sync/diff/' . $config_name);
  }

  function prepareSiteNameUpdate($new_site_name) {
    $staging = $this->container->get('config.storage.staging');
    // Create updated configuration object.
    $config_data = config('system.site')->get();
    $config_data['name'] = $new_site_name;
    $staging->write('system.site', $config_data);
  }
}
