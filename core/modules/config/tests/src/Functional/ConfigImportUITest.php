<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\InstallStorage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for importing configuration.
 *
 * @group config
 */
class ConfigImportUITest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['config', 'config_test', 'config_import_test', 'text', 'options'];

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($this->webUser);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests importing configuration.
   */
  public function testImport() {
    $name = 'system.site';
    $dynamic_name = 'config_test.dynamic.new';
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');

    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('There are no configuration changes to import.');
    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    $this->assertIdentical($sync->exists($name), TRUE, $name . ' found.');

    // Create new config entity.
    $original_dynamic_data = [
      'uuid' => '30df59bd-7b03-4cf7-bb35-d42fc49f0651',
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'new',
      'label' => 'New',
      'weight' => 0,
      'style' => '',
      'size' => '',
      'size_value' => '',
      'protected_property' => '',
    ];
    $sync->write($dynamic_name, $original_dynamic_data);
    $this->assertIdentical($sync->exists($dynamic_name), TRUE, $dynamic_name . ' found.');

    // Enable the Action and Ban modules during import. The Ban
    // module is used because it creates a table during the install. The Action
    // module is used because it creates a single simple configuration file
    // during the install.
    $core_extension = $this->config('core.extension')->get();
    $core_extension['module']['action'] = 0;
    $core_extension['module']['ban'] = 0;
    $core_extension['module'] = module_config_sort($core_extension['module']);
    // Bartik is a subtheme of classy so classy must be enabled.
    $core_extension['theme']['classy'] = 0;
    $core_extension['theme']['bartik'] = 0;
    $sync->write('core.extension', $core_extension);

    // Use the install storage so that we can read configuration from modules
    // and themes that are not installed.
    $install_storage = new InstallStorage();

    // Set the Bartik theme as default.
    $system_theme = $this->config('system.theme')->get();
    $system_theme['default'] = 'bartik';
    $sync->write('system.theme', $system_theme);

    // Read the action config from module default config folder.
    $action_settings = $install_storage->read('action.settings');
    $action_settings['recursion_limit'] = 50;
    $sync->write('action.settings', $action_settings);

    // Uninstall the Options and Text modules to ensure that dependencies are
    // handled correctly. Options depends on Text so Text should be installed
    // first. Since they were enabled during the test setup the core.extension
    // file in sync will already contain them.
    \Drupal::service('module_installer')->uninstall(['text', 'options']);

    // Set the state system to record installations and uninstallations.
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_installed', []);
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_uninstalled', []);

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertRaw('<td>' . $name);
    $this->assertRaw('<td>' . $dynamic_name);
    $this->assertRaw('<td>core.extension');
    $this->assertRaw('<td>system.theme');
    $this->assertRaw('<td>action.settings');
    $this->assertFieldById('edit-submit', t('Import all'));

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertNoRaw('<td>' . $name);
    $this->assertNoRaw('<td>' . $dynamic_name);
    $this->assertNoRaw('<td>core.extension');
    $this->assertNoRaw('<td>system.theme');
    $this->assertNoRaw('<td>action.settings');

    $this->assertNoFieldById('edit-submit', t('Import all'));

    // Verify that there are no further changes to import.
    $this->assertText(t('There are no configuration changes to import.'));

    $this->rebuildContainer();
    // Verify site name has changed.
    $this->assertIdentical($new_site_name, $this->config('system.site')->get('name'));

    // Verify that new config entity exists.
    $this->assertIdentical($original_dynamic_data, $this->config($dynamic_name)->get());

    // Verify the cache got cleared.
    $this->assertTrue(isset($GLOBALS['hook_cache_flush']));

    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module installed during import.');
    $this->assertTrue(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip exists.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('action'), 'Action module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('options'), 'Options module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('text'), 'Text module installed during import.');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    $this->assertTrue($theme_info['bartik']->status, 'Bartik theme installed during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []);
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['action', 'ban', 'text', 'options'];
    $this->assertIdentical($expected, $installed, 'Action, Ban, Text and Options modules installed in the correct order.');
    $this->assertTrue(empty($uninstalled), 'No modules uninstalled during import');

    // Verify that the action.settings configuration object was only written
    // once during the import process and only with the value set in the staged
    // configuration. This verifies that the module's default configuration is
    // used during configuration import and, additionally, that after installing
    // a module, that configuration is not synced twice.
    $recursion_limit_values = \Drupal::state()->get('ConfigImportUITest.action.settings.recursion_limit', []);
    $this->assertIdentical($recursion_limit_values, [50]);

    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['action']);
    unset($core_extension['module']['ban']);
    unset($core_extension['module']['options']);
    unset($core_extension['module']['text']);
    unset($core_extension['theme']['bartik']);
    $sync->write('core.extension', $core_extension);
    $sync->delete('action.settings');
    $sync->delete('text.settings');

    $system_theme = $this->config('system.theme')->get();
    $system_theme['default'] = 'stark';
    $system_theme['admin'] = 'stark';
    $sync->write('system.theme', $system_theme);

    // Set the state system to record installations and uninstallations.
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_installed', []);
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_uninstalled', []);

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertRaw('<td>core.extension');
    $this->assertRaw('<td>system.theme');
    $this->assertRaw('<td>action.settings');

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertNoRaw('<td>core.extension');
    $this->assertNoRaw('<td>system.theme');
    $this->assertNoRaw('<td>action.settings');

    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module uninstalled during import.');
    $this->assertFalse(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip does not exist.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('action'), 'Action module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('options'), 'Options module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('text'), 'Text module uninstalled during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []);
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['options', 'text', 'ban', 'action'];
    $this->assertIdentical($expected, $uninstalled, 'Options, Text, Ban and Action modules uninstalled in the correct order.');
    $this->assertTrue(empty($installed), 'No modules installed during import');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    $this->assertFalse(isset($theme_info['bartik']), 'Bartik theme uninstalled during import.');

    // Verify that the action.settings configuration object was only deleted
    // once during the import process.
    $delete_called = \Drupal::state()->get('ConfigImportUITest.action.settings.delete', 0);
    $this->assertIdentical($delete_called, 1, "The action.settings configuration was deleted once during configuration import.");
  }

  /**
   * Tests concurrent importing of configuration.
   */
  public function testImportLock() {
    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));

    // Acquire a fake-lock on the import mechanism.
    $config_importer = $this->configImporter();
    $this->container->get('lock.persistent')->acquire($config_importer::LOCK_NAME);

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertText(t('Another request may be synchronizing configuration already.'));

    // Release the lock, just to keep testing sane.
    $this->container->get('lock.persistent')->release($config_importer::LOCK_NAME);

    // Verify site name has not changed.
    $this->assertNotEqual($new_site_name, $this->config('system.site')->get('name'));
  }

  /**
   * Tests verification of site UUID before importing configuration.
   */
  public function testImportSiteUuidValidation() {
    $sync = \Drupal::service('config.storage.sync');
    // Create updated configuration object.
    $config_data = $this->config('system.site')->get();
    // Generate a new site UUID.
    $config_data['uuid'] = \Drupal::service('uuid')->generate();
    $sync->write('system.site', $config_data);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertText(t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'));
    $this->assertNoFieldById('edit-submit', t('Import all'));
  }

  /**
   * Tests the screen that shows differences between active and sync.
   */
  public function testImportDiff() {
    $sync = $this->container->get('config.storage.sync');
    $config_name = 'config_test.system';
    $change_key = 'foo';
    $remove_key = '404';
    $add_key = 'biff';
    $add_data = '<em>bangpow</em>';
    $change_data = '<p><em>foobar</em></p>';
    $original_data = [
      'foo' => '<p>foobar</p>',
      'baz' => '<strong>no change</strong>',
      '404' => '<em>herp</em>',
    ];
    // Update active storage to have html in config data.
    $this->config($config_name)->setData($original_data)->save();

    // Change a configuration value in sync.
    $sync_data = $original_data;
    $sync_data[$change_key] = $change_data;
    $sync_data[$add_key] = $add_data;
    unset($sync_data[$remove_key]);
    $sync->write($config_name, $sync_data);

    // Load the diff UI and verify that the diff reflects the change.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    $this->assertNoRaw('&amp;nbsp;');
    $this->assertTitle(format_string('View changes of @config_name | Drupal', ['@config_name' => $config_name]));

    // The following assertions do not use $this::assertEscaped() because
    // \Drupal\Component\Diff\DiffFormatter adds markup that signifies what has
    // changed.

    // Changed values are escaped.
    $this->assertText(Html::escape("foo: '<p><em>foobar</em></p>'"));
    $this->assertText(Html::escape("foo: '<p>foobar</p>'"));
    // The no change values are escaped.
    $this->assertText(Html::escape("baz: '<strong>no change</strong>'"));
    // Added value is escaped.
    $this->assertText(Html::escape("biff: '<em>bangpow</em>'"));
    // Deleted value is escaped.
    $this->assertText(Html::escape("404: '<em>herp</em>'"));

    // Verify diff colors are displayed.
    $result = $this->xpath('//table[contains(@class, :class)]', [':class' => 'diff']);
    $this->assertEqual(count($result), 1, "Diff UI is displaying colors.");

    // Reset data back to original, and remove a key
    $sync_data = $original_data;
    unset($sync_data[$remove_key]);
    $sync->write($config_name, $sync_data);

    // Load the diff UI and verify that the diff reflects a removed key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    // The no change values are escaped.
    $this->assertText(Html::escape("foo: '<p>foobar</p>'"));
    $this->assertText(Html::escape("baz: '<strong>no change</strong>'"));
    // Removed key is escaped.
    $this->assertText(Html::escape("404: '<em>herp</em>'"));

    // Reset data back to original and add a key
    $sync_data = $original_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Load the diff UI and verify that the diff reflects an added key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    // The no change values are escaped.
    $this->assertText(Html::escape("baz: '<strong>no change</strong>'"));
    $this->assertText(Html::escape("404: '<em>herp</em>'"));
    // Added key is escaped.
    $this->assertText(Html::escape("biff: '<em>bangpow</em>'"));
  }

  /**
   * Tests that multiple validation errors are listed on the page.
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
    $this->drupalPostForm(NULL, [], t('Import all'));

    // Verify that the validation messages appear.
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Config import validate error 1.');
    $this->assertText('Config import validate error 2.');

    // Verify site name has not changed.
    $this->assertNotEqual($new_site_name, $this->config('system.site')->get('name'));
  }

  public function testConfigUninstallConfigException() {
    $sync = $this->container->get('config.storage.sync');

    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['config']);
    $sync->write('core.extension', $core_extension);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertText('core.extension');

    // Import and verify that both do not appear anymore.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertText('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.');
  }

  public function prepareSiteNameUpdate($new_site_name) {
    $sync = $this->container->get('config.storage.sync');
    // Create updated configuration object.
    $config_data = $this->config('system.site')->get();
    $config_data['name'] = $new_site_name;
    $sync->write('system.site', $config_data);
  }

  /**
   * Tests an import that results in an error.
   */
  public function testImportErrorLog() {
    $name_primary = 'config_test.dynamic.primary';
    $name_secondary = 'config_test.dynamic.secondary';
    $sync = $this->container->get('config.storage.sync');
    $uuid = $this->container->get('uuid');

    $values_primary = [
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'primary',
      'label' => 'Primary',
      'weight' => 0,
      'style' => NULL,
      'size' => NULL,
      'size_value' => NULL,
      'protected_property' => NULL,
    ];
    $sync->write($name_primary, $values_primary);
    $values_secondary = [
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
      'status' => TRUE,
      // Add a dependency on primary, to ensure that is synced first.
      'dependencies' => [
        'config' => [$name_primary],
      ],
      'id' => 'secondary',
      'label' => 'Secondary Sync',
      'weight' => 0,
      'style' => NULL,
      'size' => NULL,
      'size_value' => NULL,
      'protected_property' => NULL,
    ];
    $sync->write($name_secondary, $values_secondary);
    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertNoText(t('There are no configuration changes to import.'));

    // Attempt to import configuration and verify that an error message appears.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertText(new FormattableMarkup('Deleted and replaced configuration entity "@name"', ['@name' => $name_secondary]));
    $this->assertText(t('The configuration was imported with errors.'));
    $this->assertNoText(t('The configuration was imported successfully.'));
    $this->assertText(t('There are no configuration changes to import.'));
  }

  /**
   * Tests the config importer cannot delete bundles with existing entities.
   *
   * @see \Drupal\Core\Entity\Event\BundleConfigImportValidate
   */
  public function testEntityBundleDelete() {
    \Drupal::service('module_installer')->install(['node']);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $node_type->id()]);
    $this->drupalGet('admin/config/development/configuration');
    // The node type, body field and entity displays will be scheduled for
    // removal.
    $this->assertText(format_string('node.type.@type', ['@type' => $node_type->id()]));
    $this->assertText(format_string('field.field.node.@type.body', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_view_display.node.@type.teaser', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_view_display.node.@type.default', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_form_display.node.@type.default', ['@type' => $node_type->id()]));

    // Attempt to import configuration and verify that an error message appears
    // and the node type, body field and entity displays are still scheduled for
    // removal.
    $this->drupalPostForm(NULL, [], t('Import all'));
    $validation_message = t('Entities exist of type %entity_type and %bundle_label %bundle. These entities need to be deleted before importing.', ['%entity_type' => $node->getEntityType()->getLabel(), '%bundle_label' => $node->getEntityType()->getBundleLabel(), '%bundle' => $node_type->label()]);
    $this->assertRaw($validation_message);
    $this->assertText(format_string('node.type.@type', ['@type' => $node_type->id()]));
    $this->assertText(format_string('field.field.node.@type.body', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_view_display.node.@type.teaser', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_view_display.node.@type.default', ['@type' => $node_type->id()]));
    $this->assertText(format_string('core.entity_form_display.node.@type.default', ['@type' => $node_type->id()]));

    // Delete the node and try to import again.
    $node->delete();
    $this->drupalPostForm(NULL, [], t('Import all'));
    $this->assertNoRaw($validation_message);
    $this->assertText(t('There are no configuration changes to import.'));
    $this->assertNoText(format_string('node.type.@type', ['@type' => $node_type->id()]));
    $this->assertNoText(format_string('field.field.node.@type.body', ['@type' => $node_type->id()]));
    $this->assertNoText(format_string('core.entity_view_display.node.@type.teaser', ['@type' => $node_type->id()]));
    $this->assertNoText(format_string('core.entity_view_display.node.@type.default', ['@type' => $node_type->id()]));
    $this->assertNoText(format_string('core.entity_form_display.node.@type.default', ['@type' => $node_type->id()]));
  }

  /**
   * Tests config importer cannot uninstall extensions which are depended on.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testExtensionValidation() {
    \Drupal::service('module_installer')->install(['node']);
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->rebuildContainer();

    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');
    // Node depends on text.
    unset($core['module']['text']);
    $module_data = system_rebuild_module_data();
    $this->assertTrue(isset($module_data['node']->requires['text']), 'The Node module depends on the Text module.');
    // Bartik depends on classy.
    unset($core['theme']['classy']);
    $theme_data = \Drupal::service('theme_handler')->rebuildThemeData();
    $this->assertTrue(isset($theme_data['bartik']->requires['classy']), 'The Bartik theme depends on the Classy theme.');
    // This module does not exist.
    $core['module']['does_not_exist'] = 0;
    // This theme does not exist.
    $core['theme']['does_not_exist'] = 0;
    $sync->write('core.extension', $core);

    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertText('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertText('Unable to uninstall the Text module since the Node module is installed.');
    $this->assertText('Unable to uninstall the Classy theme since the Bartik theme is installed.');
    $this->assertText('Unable to install the does_not_exist module since it does not exist.');
    $this->assertText('Unable to install the does_not_exist theme since it does not exist.');
  }

  /**
   * Tests that errors set in the batch and on the ConfigImporter are merged.
   */
  public function testBatchErrors() {
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    \Drupal::state()->set('config_import_steps_alter.error', TRUE);
    $this->drupalPostForm('admin/config/development/configuration', [], t('Import all'));
    $this->assertSession()->responseContains('_config_import_test_config_import_steps_alter batch error');
    $this->assertSession()->responseContains('_config_import_test_config_import_steps_alter ConfigImporter error');
    $this->assertSession()->responseContains('The configuration was imported with errors.');
  }

}
