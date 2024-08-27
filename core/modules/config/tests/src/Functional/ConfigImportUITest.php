<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for importing configuration.
 *
 * @group config
 * @group #slow
 */
class ConfigImportUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'config_test',
    'config_import_test',
    'text',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'synchronize configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($this->webUser);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests importing configuration.
   */
  public function testImport(): void {
    $name = 'system.site';
    $dynamic_name = 'config_test.dynamic.new';
    /** @var \Drupal\Core\Config\StorageInterface $sync */
    $sync = $this->container->get('config.storage.sync');

    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->pageTextContains('The staged configuration is identical to the active configuration.');
    $this->assertSession()->buttonNotExists('Import all');

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    $this->assertTrue($sync->exists($name), $name . ' found.');

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
    $this->assertTrue($sync->exists($dynamic_name), $dynamic_name . ' found.');

    // Enable the Automated Cron and Ban modules during import. The Ban
    // module is used because it creates a table during the install.
    // The Automated Cron module is used because it creates a single simple
    // configuration file during the install.
    $core_extension = $this->config('core.extension')->get();
    $core_extension['module']['automated_cron'] = 0;
    $core_extension['module']['ban'] = 0;
    $core_extension['module'] = module_config_sort($core_extension['module']);
    $core_extension['theme']['olivero'] = 0;
    $sync->write('core.extension', $core_extension);
    // Olivero ships with configuration.
    $sync->write('olivero.settings', Yaml::decode(file_get_contents('core/themes/olivero/config/install/olivero.settings.yml')));

    // Use the install storage so that we can read configuration from modules
    // and themes that are not installed.
    $install_storage = new InstallStorage();

    // Set the Olivero theme as default.
    $system_theme = $this->config('system.theme')->get();
    $system_theme['default'] = 'olivero';
    $sync->write('system.theme', $system_theme);

    // Read the automated_cron config from module default config folder.
    $settings = $install_storage->read('automated_cron.settings');
    $settings['interval'] = 10000;
    $sync->write('automated_cron.settings', $settings);

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
    $this->assertSession()->responseContains('<td>' . $name);
    $this->assertSession()->responseContains('<td>' . $dynamic_name);
    $this->assertSession()->responseContains('<td>core.extension');
    $this->assertSession()->responseContains('<td>system.theme');
    $this->assertSession()->responseContains('<td>automated_cron.settings');
    $this->assertSession()->buttonExists('Import all');

    // Import and verify that both do not appear anymore.
    $this->submitForm([], 'Import all');
    $this->assertSession()->responseNotContains('<td>' . $name);
    $this->assertSession()->responseNotContains('<td>' . $dynamic_name);
    $this->assertSession()->responseNotContains('<td>core.extension');
    $this->assertSession()->responseNotContains('<td>system.theme');
    $this->assertSession()->responseNotContains('<td>automated_cron.settings');

    $this->assertSession()->buttonNotExists('Import all');

    // Verify that there are no further changes to import.
    $this->assertSession()->pageTextContains('The staged configuration is identical to the active configuration.');

    $this->rebuildContainer();
    // Verify site name has changed.
    $this->assertSame($new_site_name, $this->config('system.site')->get('name'));

    // Verify that new config entity exists.
    $this->assertSame($original_dynamic_data, $this->config($dynamic_name)->get());

    // Verify the cache got cleared.
    $this->assertTrue(isset($GLOBALS['hook_cache_flush']));

    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module installed during import.');
    $this->assertTrue(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip exists.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('automated_cron'), 'Automated Cron module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('options'), 'Options module installed during import.');
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('text'), 'Text module installed during import.');
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('olivero'), 'Olivero theme installed during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []);
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['automated_cron', 'ban', 'text', 'options'];
    $this->assertSame($expected, $installed, 'Automated Cron, Ban, Text and Options modules installed in the correct order.');
    $this->assertEmpty($uninstalled, 'No modules uninstalled during import');

    // Verify that the automated_cron configuration object was only written
    // once during the import process and only with the value set in the staged
    // configuration. This verifies that the module's default configuration is
    // used during configuration import and, additionally, that after installing
    // a module, that configuration is not synced twice.
    $interval_values = \Drupal::state()->get('ConfigImportUITest.automated_cron.settings.interval', []);
    $this->assertSame([10000], $interval_values);

    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['automated_cron']);
    unset($core_extension['module']['ban']);
    unset($core_extension['module']['options']);
    unset($core_extension['module']['text']);
    unset($core_extension['theme']['olivero']);
    $sync->write('core.extension', $core_extension);
    $sync->delete('automated_cron.settings');
    $sync->delete('text.settings');
    $sync->delete('olivero.settings');

    $system_theme = $this->config('system.theme')->get();
    $system_theme = [
      '_core' => $system_theme['_core'],
      'admin' => 'stark',
      'default' => 'stark',
    ];
    $sync->write('system.theme', $system_theme);

    // Set the state system to record installations and uninstallations.
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_installed', []);
    \Drupal::state()->set('ConfigImportUITest.core.extension.modules_uninstalled', []);

    // Verify that both appear as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->responseContains('<td>core.extension');
    $this->assertSession()->responseContains('<td>system.theme');
    $this->assertSession()->responseContains('<td>automated_cron.settings');

    // Import and verify that both do not appear anymore.
    $this->submitForm([], 'Import all');
    $this->assertSession()->responseNotContains('<td>core.extension');
    $this->assertSession()->responseNotContains('<td>system.theme');
    $this->assertSession()->responseNotContains('<td>automated_cron.settings');

    $this->rebuildContainer();
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('ban'), 'Ban module uninstalled during import.');
    $this->assertFalse(\Drupal::database()->schema()->tableExists('ban_ip'), 'The database table ban_ip does not exist.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('automated_cron'), 'Automated cron module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('options'), 'Options module uninstalled during import.');
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('text'), 'Text module uninstalled during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []);
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['options', 'text', 'ban', 'automated_cron'];
    $this->assertSame($expected, $uninstalled, 'Options, Text, Ban and Automated Cron modules uninstalled in the correct order.');
    $this->assertEmpty($installed, 'No modules installed during import');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    $this->assertFalse(isset($theme_info['olivero']), 'Olivero theme uninstalled during import.');

    // Verify that the automated_cron.settings configuration object was only
    // deleted once during the import process.
    $delete_called = \Drupal::state()->get('ConfigImportUITest.automated_cron.settings.delete', 0);
    $this->assertSame(1, $delete_called, "The automated_cron.settings configuration was deleted once during configuration import.");
  }

  /**
   * Tests concurrent importing of configuration.
   */
  public function testImportLock(): void {
    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->pageTextNotContains('The staged configuration is identical to the active configuration.');

    // Acquire a fake-lock on the import mechanism.
    $config_importer = $this->configImporter();
    $this->container->get('lock.persistent')->acquire($config_importer::LOCK_NAME);

    // Attempt to import configuration and verify that an error message appears.
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('Another request may be synchronizing configuration already.');

    // Release the lock, just to keep testing sane.
    $this->container->get('lock.persistent')->release($config_importer::LOCK_NAME);

    // Verify site name has not changed.
    $this->assertNotEquals($this->config('system.site')->get('name'), $new_site_name);
  }

  /**
   * Tests verification of site UUID before importing configuration.
   */
  public function testImportSiteUuidValidation(): void {
    $sync = \Drupal::service('config.storage.sync');
    // Create updated configuration object.
    $config_data = $this->config('system.site')->get();
    // Generate a new site UUID.
    $config_data['uuid'] = \Drupal::service('uuid')->generate();
    $sync->write('system.site', $config_data);

    // Verify that there are configuration differences to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->pageTextContains('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.');
    $this->assertSession()->buttonNotExists('Import all');
  }

  /**
   * Tests the screen that shows differences between active and sync.
   */
  public function testImportDiff(): void {
    $sync = $this->container->get('config.storage.sync');
    $config_name = 'config_test.system';
    $change_key = 'foo';
    $remove_key = '404';
    $add_key = 'biff';
    $add_data = '<em>bangPow</em>';
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
    $this->assertSession()->responseNotContains('&amp;nbsp;');
    $this->assertSession()->titleEquals("View changes of $config_name | Drupal");

    // The following assertions do not use
    // $this->assertSession()->assertEscaped() because
    // \Drupal\Component\Diff\DiffFormatter adds markup that signifies what has
    // changed.

    // Changed values are escaped.
    $this->assertSession()->pageTextContains("foo: '<p><em>foobar</em></p>'");
    $this->assertSession()->pageTextContains("foo: '<p>foobar</p>'");
    // The no change values are escaped.
    $this->assertSession()->pageTextContains("baz: '<strong>no change</strong>'");
    // Added value is escaped.
    $this->assertSession()->pageTextContains("biff: '<em>bangPow</em>'");
    // Deleted value is escaped.
    $this->assertSession()->pageTextContains("404: '<em>herp</em>'");

    // Verify diff colors are displayed.
    $this->assertSession()->elementsCount('xpath', '//table[contains(@class, "diff")]', 1);

    // Reset data back to original, and remove a key
    $sync_data = $original_data;
    unset($sync_data[$remove_key]);
    $sync->write($config_name, $sync_data);

    // Load the diff UI and verify that the diff reflects a removed key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    // The no change values are escaped.
    $this->assertSession()->pageTextContains("foo: '<p>foobar</p>'");
    $this->assertSession()->pageTextContains("baz: '<strong>no change</strong>'");
    // Removed key is escaped.
    $this->assertSession()->pageTextContains("404: '<em>herp</em>'");

    // Reset data back to original and add a key
    $sync_data = $original_data;
    $sync_data[$add_key] = $add_data;
    $sync->write($config_name, $sync_data);

    // Load the diff UI and verify that the diff reflects an added key.
    $this->drupalGet('admin/config/development/configuration/sync/diff/' . $config_name);
    // The no change values are escaped.
    $this->assertSession()->pageTextContains("baz: '<strong>no change</strong>'");
    $this->assertSession()->pageTextContains("404: '<em>herp</em>'");
    // Added key is escaped.
    $this->assertSession()->pageTextContains("biff: '<em>bangPow</em>'");
  }

  /**
   * Tests that multiple validation errors are listed on the page.
   */
  public function testImportValidation(): void {
    // Set state value so that
    // \Drupal\config_import_test\EventSubscriber::onConfigImportValidate() logs
    // validation errors.
    \Drupal::state()->set('config_import_test.config_import_validate_fail', TRUE);
    // Ensure there is something to import.
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->pageTextNotContains('The staged configuration is identical to the active configuration.');
    $this->submitForm([], 'Import all');

    // Verify that the validation messages appear.
    $this->assertSession()->pageTextContains('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertSession()->pageTextContains('Config import validate error 1.');
    $this->assertSession()->pageTextContains('Config import validate error 2.');

    // Verify site name has not changed.
    $this->assertNotEquals($this->config('system.site')->get('name'), $new_site_name);
  }

  public function testConfigUninstallConfigException(): void {
    $sync = $this->container->get('config.storage.sync');

    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['config']);
    $sync->write('core.extension', $core_extension);

    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->pageTextContains('core.extension');

    // Import and verify that both do not appear anymore.
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.');
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
  public function testImportErrorLog(): void {
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
    $this->assertSession()->pageTextNotContains('The staged configuration is identical to the active configuration.');

    // Attempt to import configuration and verify that an error message appears.
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('Deleted and replaced configuration entity "' . $name_secondary . '"');
    $this->assertSession()->pageTextContains('The configuration was imported with errors.');
    $this->assertSession()->pageTextNotContains('The configuration was imported successfully.');
    $this->assertSession()->pageTextContains('The staged configuration is identical to the active configuration.');
  }

  /**
   * Tests the config importer cannot delete bundles with existing entities.
   *
   * @see \Drupal\Core\Entity\Event\BundleConfigImportValidate
   */
  public function testEntityBundleDelete(): void {
    \Drupal::service('module_installer')->install(['node']);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $node_type->id()]);
    $this->drupalGet('admin/config/development/configuration');
    // The node type, body field and entity displays will be scheduled for
    // removal.
    $this->assertSession()->pageTextContains('node.type.' . $node_type->id());
    $this->assertSession()->pageTextContains('field.field.node.' . $node_type->id() . '.body');
    $this->assertSession()->pageTextContains('core.entity_view_display.node.' . $node_type->id() . '.teaser');
    $this->assertSession()->pageTextContains('core.entity_view_display.node.' . $node_type->id() . '.default');
    $this->assertSession()->pageTextContains('core.entity_form_display.node.' . $node_type->id() . '.default');

    // Attempt to import configuration and verify that an error message appears
    // and the node type, body field and entity displays are still scheduled for
    // removal.
    $this->submitForm([], 'Import all');
    $validation_message = "Entities exist of type {$node->getEntityType()->getLabel()} and {$node->getEntityType()->getBundleLabel()} {$node_type->label()}. These entities need to be deleted before importing.";
    $this->assertSession()->pageTextContains($validation_message);
    $this->assertSession()->pageTextContains('node.type.' . $node_type->id());
    $this->assertSession()->pageTextContains('field.field.node.' . $node_type->id() . '.body');
    $this->assertSession()->pageTextContains('core.entity_view_display.node.' . $node_type->id() . '.teaser');
    $this->assertSession()->pageTextContains('core.entity_view_display.node.' . $node_type->id() . '.default');
    $this->assertSession()->pageTextContains('core.entity_form_display.node.' . $node_type->id() . '.default');

    // Delete the node and try to import again.
    $node->delete();
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextNotContains($validation_message);
    $this->assertSession()->pageTextContains('The staged configuration is identical to the active configuration.');
    $this->assertSession()->pageTextNotContains('node.type.' . $node_type->id());
    $this->assertSession()->pageTextNotContains('field.field.node.' . $node_type->id() . '.body');
    $this->assertSession()->pageTextNotContains('core.entity_view_display.node.' . $node_type->id() . '.teaser');
    $this->assertSession()->pageTextNotContains('core.entity_view_display.node.' . $node_type->id() . '.default');
    $this->assertSession()->pageTextNotContains('core.entity_form_display.node.' . $node_type->id() . '.default');
  }

  /**
   * Tests config importer cannot uninstall extensions which are depended on.
   *
   * @see \Drupal\Core\EventSubscriber\ConfigImportSubscriber
   */
  public function testExtensionValidation(): void {
    \Drupal::service('module_installer')->install(['node']);
    \Drupal::service('theme_installer')->install(['test_subtheme']);
    $this->rebuildContainer();

    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);
    $core = $sync->read('core.extension');
    // Node depends on text.
    unset($core['module']['text']);
    $module_data = $this->container->get('extension.list.module')->getList();
    $this->assertTrue(isset($module_data['node']->requires['text']), 'The Node module depends on the Text module.');
    unset($core['theme']['test_basetheme']);
    $theme_data = \Drupal::service('extension.list.theme')->reset()->getList();
    $this->assertTrue(isset($theme_data['test_subtheme']->requires['test_basetheme']), 'The Test Subtheme theme depends on the Test Basetheme theme.');
    // This module does not exist.
    $core['module']['does_not_exist'] = 0;
    // This theme does not exist.
    $core['theme']['does_not_exist'] = 0;
    $sync->write('core.extension', $core);

    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->pageTextContains('The configuration cannot be imported because it failed validation for the following reasons:');
    $this->assertSession()->pageTextContains('Unable to uninstall the Text module since the Node module is installed.');
    $this->assertSession()->pageTextContains('Unable to uninstall the Theme test base theme theme since the Theme test subtheme theme is installed.');
    $this->assertSession()->pageTextContains('Unable to install the does_not_exist module since it does not exist.');
    $this->assertSession()->pageTextContains('Unable to install the does_not_exist theme since it does not exist.');
  }

  /**
   * Tests that errors set in the batch and on the ConfigImporter are merged.
   */
  public function testBatchErrors(): void {
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    \Drupal::state()->set('config_import_steps_alter.error', TRUE);
    $this->drupalGet('admin/config/development/configuration');
    $this->submitForm([], 'Import all');
    $this->assertSession()->responseContains('_config_import_test_config_import_steps_alter batch error');
    $this->assertSession()->responseContains('_config_import_test_config_import_steps_alter ConfigImporter error');
    $this->assertSession()->responseContains('The configuration was imported with errors.');
  }

}
