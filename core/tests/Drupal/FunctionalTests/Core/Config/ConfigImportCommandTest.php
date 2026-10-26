<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Config;

use Drupal\Core\Command\Exception\UserAbortException;
use Drupal\Core\Config\Command\ConfigImportCommand;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterFactory;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ModuleWeight;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

// cspell:ignore arrr

/**
 * Tests the 'config:import' command.
 *
 * This command imports staged configuration the same way a user can from the
 * Configuration module's synchronize page, except run from the command line.
 *
 * This test is a functional test because we use the config importer.
 *
 * @see \Drupal\Tests\config\Functional\ConfigImportUITest
 */
#[Group('config')]
#[RunTestsInSeparateProcesses]
#[CoversClass(ConfigImportCommand::class)]
class ConfigImportCommandTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests importing configuration.
   *
   * @see \Drupal\Tests\config\Functional\ConfigImportUITest::testImport()
   */
  public function testImport(): void {
    $name = 'system.site';
    $dynamic_name = 'config_test.dynamic.new';
    $sync = $this->container->get('config.storage.sync');

    $tester = $this->commandTester();
    $code = $tester->execute([]);
    self::assertSame(Command::SUCCESS, $code);
    self::assertStringContainsString('There are no changes to import.', $tester->getDisplay());

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomMachineName();
    $this->prepareSiteNameUpdate($new_site_name);
    self::assertTrue($sync->exists($name), $name . ' found.');

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
    self::assertTrue($sync->exists($dynamic_name), $dynamic_name . ' found.');

    // Enable the Automated Cron and config_install_schema_test modules during
    // import. The config_install_schema_test module is used because it creates
    // a table during the install. The Automated Cron module is used because it
    // creates a single simple configuration file during the install.
    $core_extension = $this->config('core.extension')->get();
    $core_extension['module']['automated_cron'] = 0;
    $core_extension['module']['config_install_schema_test'] = 0;
    $core_extension['module'] = \Drupal::service(ModuleWeight::class)->sort($core_extension['module']);
    $core_extension['theme']['test_theme'] = 0;
    $sync->write('core.extension', $core_extension);

    // Use the install storage so that we can read configuration from modules
    // and themes that are not installed.
    $install_storage = new InstallStorage();

    // Set the test theme as default.
    $system_theme = $this->config('system.theme')->get();
    $system_theme['default'] = 'test_theme';
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

    // Run the import command.
    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::SUCCESS, $code);
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
    // Verify that both appear as ready to import.
    self::assertStringContainsString($name . ' Update', $display);
    self::assertStringContainsString($dynamic_name . ' Create', $display);
    self::assertStringContainsString('core.extension Update', $display);
    self::assertStringContainsString('system.theme Update', $display);
    self::assertStringContainsString('automated_cron.settings Create', $display);

    self::assertStringContainsString('The configuration was imported successfully.', $display);

    // Verify that there are no further changes to import.
    $tester = $this->commandTester();
    $code = $tester->execute([]);
    $this->assertSame(Command::SUCCESS, $code);
    $this->assertStringContainsString('There are no changes to import.', $tester->getDisplay());

    $this->rebuildContainer();
    // Verify site name has changed.
    self::assertSame($new_site_name, $this->config($name)->get('name'));

    // Verify that new config entity exists.
    self::assertSame($original_dynamic_data, $this->config($dynamic_name)->get());

    self::assertTrue(\Drupal::moduleHandler()->moduleExists('config_install_schema_test'), 'config_install_schema_test module installed during import.');
    self::assertTrue(\Drupal::database()->schema()->tableExists('config_install_schema_test_ip'), 'The database table config_install_schema_test_ip exists.');
    self::assertTrue(\Drupal::moduleHandler()->moduleExists('automated_cron'), 'Automated Cron module installed during import.');
    self::assertTrue(\Drupal::moduleHandler()->moduleExists('options'), 'Options module installed during import.');
    self::assertTrue(\Drupal::moduleHandler()->moduleExists('text'), 'Text module installed during import.');
    self::assertTrue(\Drupal::service('theme_handler')->themeExists('test_theme'), 'Test theme installed during import.');

    // Ensure installations and uninstallation occur as expected.
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['automated_cron', 'config_install_schema_test', 'text', 'options'];
    $installed = \Drupal::state()->get('config_import_test_modules_installed.list');
    self::assertSame($expected, $installed, 'Automated Cron, config_install_schema_test, Text and Options modules installed in the correct order.');
    self::assertEmpty($uninstalled, 'No modules uninstalled during import');

    // Verify that the automated_cron configuration object was only written
    // once during the import process and only with the value set in the staged
    // configuration. This verifies that the module's default configuration is
    // used during configuration import and, additionally, that after installing
    // a module, that configuration is not synced twice.
    $interval_values = \Drupal::state()->get('ConfigImportUITest.automated_cron.settings.interval', []);
    self::assertSame([10000], $interval_values);

    $core_extension = $this->config('core.extension')->get();
    unset($core_extension['module']['automated_cron']);
    unset($core_extension['module']['config_install_schema_test']);
    unset($core_extension['module']['options']);
    unset($core_extension['module']['text']);
    unset($core_extension['theme']['test_theme']);
    $sync->write('core.extension', $core_extension);
    $sync->delete('automated_cron.settings');
    $sync->delete('text.settings');

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

    // Run the import command but cancel it.
    $tester = $this->commandTester();
    $tester->setInputs(['no']);
    try {
      $tester->execute([], ['interactive' => TRUE]);
      $this->fail('Expected a UserAbortException to be thrown.');
    }
    catch (UserAbortException) {
      // Expected.
    }
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
    self::assertStringContainsString('core.extension Update', $display);
    self::assertStringContainsString('system.theme Update', $display);
    self::assertStringContainsString('automated_cron.settings Delete', $display);
    self::assertStringContainsString('text.settings Delete', $display);

    self::assertStringContainsString('Import the listed configuration changes?', $display);
    self::assertStringNotContainsString('The configuration was imported successfully.', $display);

    self::assertEmpty(\Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []));
    self::assertEmpty(\Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []));
    self::assertTrue(\Drupal::moduleHandler()->moduleExists('automated_cron'), 'Automated Cron module still installed.');

    // Run the import command and confirm it.
    $tester = $this->commandTester();
    $tester->setInputs(['yes']);
    $code = $tester->execute([], ['interactive' => TRUE]);
    self::assertSame(Command::SUCCESS, $code);
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
    self::assertStringContainsString('core.extension Update', $display);
    self::assertStringContainsString('system.theme Update', $display);
    self::assertStringContainsString('automated_cron.settings Delete', $display);
    self::assertStringContainsString('text.settings Delete', $display);

    self::assertStringContainsString('Import the listed configuration changes?', $display);
    self::assertStringContainsString('The configuration was imported successfully.', $display);

    $this->rebuildContainer();
    self::assertFalse(\Drupal::moduleHandler()->moduleExists('config_install_schema_test'), 'config_install_schema_test module uninstalled during import.');
    self::assertFalse(\Drupal::database()->schema()->tableExists('config_install_schema_test_ip'), 'The database table config_install_schema_test_ip does not exist.');
    self::assertFalse(\Drupal::moduleHandler()->moduleExists('automated_cron'), 'Automated cron module uninstalled during import.');
    self::assertFalse(\Drupal::moduleHandler()->moduleExists('options'), 'Options module uninstalled during import.');
    self::assertFalse(\Drupal::moduleHandler()->moduleExists('text'), 'Text module uninstalled during import.');

    // Ensure installations and uninstallation occur as expected.
    $installed = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_installed', []);
    $uninstalled = \Drupal::state()->get('ConfigImportUITest.core.extension.modules_uninstalled', []);
    $expected = ['options', 'text', 'config_install_schema_test', 'automated_cron'];
    self::assertSame($expected, $uninstalled, 'Options, Text, config_install_schema_test and Automated Cron modules uninstalled in the correct order.');
    self::assertEmpty($installed, 'No modules installed during import');

    $theme_info = \Drupal::service('theme_handler')->listInfo();
    self::assertFalse(isset($theme_info['test_theme']), 'Test theme uninstalled during import.');

    // Verify that the automated_cron.settings configuration object was only
    // deleted once during the import process.
    $delete_called = \Drupal::state()->get('ConfigImportUITest.automated_cron.settings.delete', 0);
    self::assertSame(1, $delete_called, "The automated_cron.settings configuration was deleted once during configuration import.");
  }

  /**
   * Tests concurrent importing of configuration.
   *
   * @see \Drupal\Tests\config\Functional\ConfigImportUITest::testImportLock()
   */
  public function testImportLock(): void {
    $new_site_name = 'Config import test ' . $this->randomMachineName();
    $this->prepareSiteNameUpdate($new_site_name);

    // Acquire a fake lock on the import mechanism.
    $lock = $this->container->get('lock.persistent');
    $lock->acquire(ConfigImporter::LOCK_NAME);

    // Attempt to import configuration and verify that an error message appears.
    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::FAILURE, $code);
    self::assertStringContainsString('Another request may be synchronizing configuration already.', $tester->getDisplay());

    // Release the lock, just to keep testing sane.
    $lock->release(ConfigImporter::LOCK_NAME);

    // Verify site name has not changed.
    self::assertNotEquals($new_site_name, $this->config('system.site')->get('name'));
  }

  /**
   * Tests verification of site UUID before importing configuration.
   *
   * @see \Drupal\Tests\config\Functional\ConfigImportUITest::testImportSiteUuidValidation()
   */
  public function testImportSiteUuidValidation(): void {
    $sync = $this->container->get('config.storage.sync');
    $config_data = $this->config('system.site')->get();
    $config_data['uuid'] = $this->container->get('uuid')->generate();
    $sync->write('system.site', $config_data);

    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::FAILURE, $code);
    self::assertStringContainsString('Site UUID in source storage does not match the target storage', $tester->getDisplay());
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

    // Run the import command.
    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::FAILURE, $code);
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
    // Verify that both appear as ready to import.
    self::assertStringContainsString($name_primary . ' Create', $display);
    self::assertStringContainsString($name_secondary . ' Create', $display);
    self::assertStringContainsString('[ERROR] Deleted and replaced configuration entity "' . $name_secondary . '"', $display);

    self::assertStringContainsString('[ERROR] The configuration was imported with errors.', $display);
    self::assertStringNotContainsString('The configuration was imported successfully.', $display);
  }

  /**
   * Tests that errors set in the batch and on the ConfigImporter are merged.
   */
  public function testBatchErrors(): void {
    $new_site_name = 'Config import test ' . $this->randomString();
    $this->prepareSiteNameUpdate($new_site_name);
    \Drupal::state()->set('config_import_steps_alter.error', TRUE);

    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::FAILURE, $code);
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
    // The dr command does not do batches yet.
    self::assertStringNotContainsString('[ERROR] _config_import_test_config_import_steps_alter batch error', $display);
    self::assertStringContainsString('[ERROR] _config_import_test_config_import_steps_alter ConfigImporter error', $display);
    self::assertStringNotContainsString('The configuration was imported successfully.', $display);
    self::assertStringContainsString('The configuration was imported with errors.', $display);
  }

  /**
   * Tests that import storage transformation event subscribers run.
   *
   * @see \Drupal\Tests\config\Functional\TransformedConfigExportImportUITest
   */
  public function testImportTransformation(): void {
    \Drupal::service('module_installer')->install(['config_transformer_test']);
    $new_site_name = 'Config import test ' . $this->randomMachineName();
    $this->prepareSiteNameUpdate($new_site_name);

    $active_name = $this->config('system.site')->get('name');

    $tester = $this->commandTester();
    $code = $tester->execute(['--yes' => TRUE]);
    self::assertSame(Command::SUCCESS, $code);

    // \Drupal\config_transformer_test\EventSubscriber::onImportTransform()
    // ignores the staged site name and appends " Arrr" to whatever name is
    // currently active, so the transformed value is imported instead.
    self::assertSame($active_name . ' Arrr', $this->config('system.site')->get('name'));
  }

  /**
   * Prepares a site name update by modifying the synchronized configuration.
   *
   * @see \Drupal\Tests\config\Functional\ConfigImportUITest::prepareSiteNameUpdate()
   */
  private function prepareSiteNameUpdate(string $new_site_name): void {
    $sync = $this->container->get('config.storage.sync');
    $config_data = $this->config('system.site')->get();
    $config_data['name'] = $new_site_name;
    $sync->write('system.site', $config_data);
  }

  /**
   * Builds a command tester for the 'config:import' command.
   */
  private function commandTester(): CommandTester {
    $command = new ConfigImportCommand(
      $this->container->get('config.storage'),
      $this->container->get('config.storage.sync'),
      $this->container->get(ImportStorageTransformer::class),
      $this->container->get(ConfigImporterFactory::class),
    );
    return new CommandTester($command);
  }

}
