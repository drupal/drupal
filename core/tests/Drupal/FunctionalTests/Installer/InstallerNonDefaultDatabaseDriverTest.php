<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleUninstallValidatorException;

// cspell:ignore drupaldriver testdriverdatabasedrivertestmysql
// cspell:ignore testdriverdatabasedrivertestpgsql

/**
 * Tests the interactive installer.
 *
 * @group Installer
 */
class InstallerNonDefaultDatabaseDriverTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the test database driver in use.
   * @var string
   */
  protected $testDriverName;

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings(): void {
    $driver = Database::getConnection()->driver();
    if (!in_array($driver, ['mysql', 'pgsql'])) {
      $this->markTestSkipped("This test does not support the {$driver} database driver.");
    }
    $driverNamespace = Database::getConnection()->getConnectionOptions()['namespace'];
    $this->testDriverName = 'DriverTest' . ucfirst($driver);
    $testDriverNamespace = "Drupal\\driver_test\\Driver\\Database\\{$this->testDriverName}";

    // Assert that we are using the database drivers from the driver_test module.
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-driver-drupaldriver-testdriverdatabasedrivertestmysql"]', 'MySQL by the driver_test module');
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-driver-drupaldriver-testdriverdatabasedrivertestpgsql"]', 'PostgreSQL by the driver_test module');

    $settings = $this->parameters['forms']['install_settings_form'];

    $settings['driver'] = $testDriverNamespace;
    $settings[$testDriverNamespace] = $settings[$driverNamespace];
    unset($settings[$driverNamespace]);
    $edit = $this->translatePostValues($settings);
    $this->submitForm($edit, $this->translations['Save and continue']);
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that in the settings.php the database connection array has the
    // correct values set.
    $installedDatabaseSettings = $this->getInstalledDatabaseSettings();
    $this->assertSame("Drupal\\driver_test\\Driver\\Database\\{$this->testDriverName}", $installedDatabaseSettings['default']['default']['namespace']);
    $this->assertSame($this->testDriverName, $installedDatabaseSettings['default']['default']['driver']);
    $this->assertSame("core/modules/system/tests/modules/driver_test/src/Driver/Database/{$this->testDriverName}/", $installedDatabaseSettings['default']['default']['autoload']);
    $this->assertEquals([
      'mysql' => [
        'namespace' => 'Drupal\\mysql',
        'autoload' => 'core/modules/mysql/src/',
      ],
      'pgsql' => [
        'namespace' => 'Drupal\\pgsql',
        'autoload' => 'core/modules/pgsql/src/',
      ],
    ], $installedDatabaseSettings['default']['default']['dependencies']);

    // Assert that the module "driver_test" has been installed.
    $this->assertEquals(\Drupal::service('module_handler')->getModule('driver_test'), new Extension($this->root, 'module', 'core/modules/system/tests/modules/driver_test/driver_test.info.yml'));

    // Change the default database connection to use the database driver from
    // the module "driver_test".
    $connection_info = Database::getConnectionInfo();
    $driver_test_connection = $connection_info['default'];
    $driver_test_connection['driver'] = $this->testDriverName;
    $driver_test_connection['namespace'] = 'Drupal\\driver_test\\Driver\\Database\\' . $this->testDriverName;
    $driver_test_connection['autoload'] = "core/modules/system/tests/modules/driver_test/src/Driver/Database/{$this->testDriverName}/";
    Database::renameConnection('default', 'original_database_connection');
    Database::addConnectionInfo('default', 'default', $driver_test_connection);

    // The module "driver_test" should not be uninstallable, because it is
    // providing the database driver.
    try {
      $this->container->get('module_installer')->uninstall(['driver_test']);
      $this->fail('Uninstalled driver_test module.');
    }
    catch (ModuleUninstallValidatorException $e) {
      $this->assertStringContainsString("The module 'Contrib database driver test' is providing the database driver '{$this->testDriverName}'.", $e->getMessage());
    }

    // Restore the old database connection.
    Database::addConnectionInfo('default', 'default', $connection_info['default']);
  }

  /**
   * Returns the databases setup from the SUT's settings.php.
   *
   * @return array<string,mixed>
   *   The value of the $databases variable.
   */
  protected function getInstalledDatabaseSettings(): array {
    // The $app_root and $site_path variables are required by the settings.php
    // file to be parsed correctly. The $databases variable is set in the
    // included file, we need to inform PHPStan about that since PHPStan itself
    // is unable to determine it.
    $app_root = $this->container->getParameter('app.root');
    $site_path = $this->siteDirectory;
    include $app_root . '/' . $site_path . '/settings.php';
    assert(isset($databases));
    return $databases;
  }

}
