<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigImportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\InstallStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the user interface for importing/exporting configuration.
 *
 * @group config
 */
class ConfigImportUITest extends WebTestBase {

  // Enable the Options and Text modules to ensure dependencies are handled
  // correctly.
  public static $modules = array('config', 'config_test', 'config_import_test', 'text', 'options');

  protected function setUp() {
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
    /** @var \Drupal\Core\Config\StorageInterface $staging */
    $staging = $this->container->get('config.storage.staging');

    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('There are no configuration changes to import.');
    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    $this->assertIdentical($staging->exists($name), TRUE, $name . ' found.');

    // Create new config entity.
    $original_dynamic_data = array(
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->id,
      'status' => TRUE,
      'dependencies' => array(),
      'id' => 'new',
      'label' => 'New',
      'weight' => 0,
      'style' => '',
      'test_dependencies' => array(),
      'protected_property' => '',
    );
    $staging->write($dynamic_name, $original_dynamic_data);
    $this->assertIdentical($staging->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Enable the Action and Ban modules during import. The Ban
    // module is used because it creates a table during the install. The Action
    // module is used because it creates a single simple configuration file
    // during the install.
    $core_extension = \Drupal::config('core.extension')->get();
    $core_extension['module']['action'] = 0;
    $core_extension['module']['ban'] = 0;
    $core_extension['module'] = module_config_sort($core_extension['module']);
    $core_extension['theme']['bartik'] = 0;
    $staging->write('core.extension', $core_extension);

    // Use the install storage so that we can read configuration from modules
    // and themes that are not installed.
    $install_storage = new InstallStorage();

    // Set the Bartik theme as default.
    $system_theme = \Drupal::config('system.theme')->get();
    $system_theme['default'] = 'bartik';
    $staging->write('system.theme', $system_theme);
    $staging->write('bartik.settings', $install_storage->read('bartik.settings'));

    // Read the action config from module default config folder.
    $action_settings = $install_storage->read('action.settings');
    $action_settings['recursion_limit'] = 50;
    $staging->write('action.settings', $action_settings);

    // Uninstall the Options and Text modules to ensure that dependencies are
    // handled correctly. Options depends on Text so Text should be installed
    // first. Since they were enabled during the test setup the core.extension
    // file in staging will already contain them.
    \Drupal::moduleHandler()->uninstall(array('text', 'options'));

    // Set the state system to record installations and uninstallations.
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_installed', array());
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_uninstalled', array());

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertText($name);
    $this->assertText($dynamic_name);
    $this->assertText('core.extension');
    $this->assertText('system.theme');
    $this->assertText('action.settings');
    $this->assertText('bartik.settings');
    $this->assertFieldById('edit-submit', t('Import all'));

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertNoText($name);
    $this->assertNoText($dynamic_name);
    $this->assertNoText('core.extension');
    $this->assertNoText('system.theme');
    $this->assertNoText('action.settings');
    $this->assertNoText('bartik.settings');

    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Verify that there are no further changes to import.
    $this->assertText(t('There are no configuration changes to import.'));

    // Verify site name has changed.
    $this->assertIdentical($new_site_name, \Drupal::config('system.site')->get('name'));

    // Verify that new config entity exists.
    $this->assertIdentical($original_dynamic_data, \Drupal::config($dynamic_name)->get());

    // Verify the cache got cleared.
    $this->assertTrue(isset($GLOBALS['hook_cache_flush']));

    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module installed during import.');
    $this->assertTrue(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip exists.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('action'), 'Action module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('options'), 'Options module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('text'), 'Text module installed during import.');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    $this->assertTrue($theme_info['bartik']->status, 'Bartik theme enabled during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', array());
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', array());
    $expected = array('action', 'ban', 'text', 'options');
    $this->assertIdentical($expected, $installed, 'Action, Ban, Text and Options modules installed in the correct order.');
    $this->assertTrue(empty($uninstalled), 'No modules uninstalled during import');

    // Verify that the action.settings configuration object was only written
    // once during the import process and only with the value set in the staged
    // configuration. This verifies that the module's default configuration is
    // used during configuration import and, additionally, that after installing
    // a module, that configuration is not synced twice.
    $recursion_limit_values = \Drupal::state()->get('ConfigImportUITest.action.settings.recursion_limit', array());
    $this->assertIdentical($recursion_limit_values, array(50));

    $core_extension = \Drupal::config('core.extension')->get();
    unset($core_extension['module']['action']);
    unset($core_extension['module']['ban']);
    unset($core_extension['module']['options']);
    unset($core_extension['module']['text']);
    unset($core_extension['theme']['bartik']);
    $core_extension['disabled']['theme']['bartik'] = 0;
    $staging->write('core.extension', $core_extension);
    $staging->delete('action.settings');
    $staging->delete('text.settings');

    $system_theme = \Drupal::config('system.theme')->get();
    $system_theme['default'] = 'stark';
    $system_theme['admin'] = 'stark';
    $staging->write('system.theme', $system_theme);

    // Set the state system to record installations and uninstallations.
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_installed', array());
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_uninstalled', array());

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('core.extension');
    $this->assertText('system.theme');
    $this->assertText('action.settings');

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertNoText('core.extension');
    $this->assertNoText('system.theme');
    $this->assertNoText('action.settings');

    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module uninstalled during import.');
    $this->assertFalse(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip does not exist.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('action'), 'Action module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('options'), 'Options module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('text'), 'Text module uninstalled during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', array());
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', array());
    $expected = array('options', 'text', 'ban', 'action');
    $this->assertIdentical($expected, $uninstalled, 'Options, Text, Ban and Action modules uninstalled in the correct order.');
    $this->assertTrue(empty($installed), 'No modules installed during import');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    $this->assertFalse(isset($theme_info['bartik']), 'Bartik theme disabled during import.');

    // Verify that the action.settings configuration object was only deleted
    // once during the import process.
    $delete_called = \Drupal::state()->get('ConfigImportUITest.action.settings.delete', 0);
    $this->assertIdentical($delete_called, 1, "The action.settings configuration was deleted once during configuration import.");
  }

  /**
   * Tests concurrent importing of configuration.
   */
  function testImportLock() {
    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));

    // Acquire a fake-lock on the import mechanism.
    $config_importer = $this->configImporter();
    $this->container->get('lock')->acquire($config_importer::LOCK_ID);

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertText(t('Another request may be synchronizing configuration already.'));

    // Release the lock, just to keep testing sane.
    $this->container->get('lock')->release($config_importer::LOCK_ID);

    // Verify site name has not changed.
    $this->assertNotEqual($new_site_name, \Drupal::config('system.site')->get('name'));
  }

  /**
   * Tests verification of site UUID before importing configuration.
   */
  function testImportSiteUuidValidation() {
    $staging = \Drupal::service('config.storage.staging');
    // Create updated configuration object.
    $config_data = \Drupal::config('system.site')->get();
    // Generate a new site UUID.
    $config_data['uuid'] = \Drupal::service('uuid')->generate();
    $staging->write('system.site', $config_data);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertText(t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'));
    $this->assertNoFieldById('edit-submit', t('Import all'));
  }

  /**
   * Tests the screen that shows differences between active and staging.
   */
  function testImportDiff() {
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
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    $this->assertTitle(format_string('View changes of @config_name | Drupal', array('@config_name' => $config_name)));

    // Reset data back to original, and remove a key
    $staging_data = $original_data;
    unset($staging_data[$remove_key]);
    $staging->write($config_name, $staging_data);

    // Load the diff UI and verify that the diff reflects a removed key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);

    // Reset data back to original and add a key
    $staging_data = $original_data;
    $staging_data[$add_key] = $add_data;
    $staging->write($config_name, $staging_data);

    // Load the diff UI and verify that the diff reflects an added key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
  }

  /**
   * Tests that mutliple validation errors are listed on the page.
   */
  public function testImportValidation() {
    // Set state value so that
    // \Drupal\config_import_test\EventSubscriber::onConfigImportValidate() logs
    // validation errors.
    \Drupal::state()->set('config_import_test.config_import_validate_fail', TRUE);
    // Ensure there is something to import.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));
    $this->drupalPostForm(NULL, array(), t('Import all'));

    // Verify that the validation messages appear.
    $this->assertText('The configuration synchronization failed validation.');
    $this->assertText('Config import validate error 1.');
    $this->assertText('Config import validate error 2.');

    // Verify site name has not changed.
    $this->assertNotEqual($new_site_name, \Drupal::config('system.site')->get('name'));
  }

  public function testConfigUninstallConfigException() {
    $staging = $this->container->get('config.storage.staging');

    $core_extension = \Drupal::config('core.extension')->get();
    unset($core_extension['module']['config']);
    $staging->write('core.extension', $core_extension);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('core.extension');

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertText('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.');
  }

  function prepareSiteNameUpdate($new_site_name) {
    $staging = $this->container->get('config.storage.staging');
    // Create updated configuration object.
    $config_data = \Drupal::config('system.site')->get();
    $config_data['name'] = $new_site_name;
    $staging->write('system.site', $config_data);
  }

  /**
   * Tests an import that results in an error.
   */
  function testImportErrorLog() {
    $name_primary = 'config_test.dynamic.primary';
    $name_secondary = 'config_test.dynamic.secondary';
    $staging = $this->container->get('config.storage.staging');
    $uuid = $this->container->get('uuid');

    $values_primary = array(
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => array(),
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 0,
      'style' => NULL,
      'test_dependencies' => array(),
      'protected_property' => null,
    );
    $staging->write($name_primary, $values_primary);
    $values_secondary = array(
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
      'status' => TRUE,
      // Add a dependency on primary, to ensure that is synced first.
      'dependencies' => array(
        'entity' => array($name_primary),
      ),
      'id' => 'secondary',
      'label' => 'Secondary Sync',
      'weight' => 0,
      'style' => NULL,
      'test_dependencies' => array(),
      'protected_property' => null,
    );
    $staging->write($name_secondary, $values_secondary);
    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPostForm(NULL, array(), t('Import all'));
    $this->assertText(String::format('Deleted and replaced configuration entity "@name"', array('@name' => $name_secondary)));
    $this->assertText(t('The configuration was imported with errors.'));
    $this->assertNoText(t('The configuration was imported successfully.'));
    $this->assertText(t('There are no configuration changes to import.'));
  }

}
