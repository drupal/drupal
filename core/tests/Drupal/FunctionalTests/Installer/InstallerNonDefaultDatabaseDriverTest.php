<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

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
   *
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

    // Assert that we are using the database drivers from the driver_test
    // module.
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

    // Assert that the module "driver_test" and its dependencies have been
    // installed.
    $this->drupalGet('admin/modules');
    $this->assertSession()->checkboxChecked('modules[driver_test][enable]');
    $this->assertSession()->checkboxChecked('modules[mysql][enable]');
    $this->assertSession()->checkboxChecked('modules[pgsql][enable]');

    // The module "driver_test" can not be uninstalled, because it is providing
    // the database driver. Also, the "mysql" and "pgsql" modules can not be
    // uninstalled being dependencies of the "driver_test" module.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->elementTextContains('xpath', '//tr[@data-drupal-selector="edit-driver-test"]', "The following reason prevents Contrib database driver test from being uninstalled: The module 'Contrib database driver test' is providing the database driver '{$this->testDriverName}'.");
    $this->assertSession()->elementTextContains('xpath', '//tr[@data-drupal-selector="edit-mysql"]', "The following reason prevents MySQL from being uninstalled: Required by: driver_test");
    $this->assertSession()->elementTextContains('xpath', '//tr[@data-drupal-selector="edit-pgsql"]', "The following reason prevents PostgreSQL from being uninstalled: Required by: driver_test");
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
