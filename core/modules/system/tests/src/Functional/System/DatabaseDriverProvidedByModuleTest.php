<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class DatabaseDriverProvidedByModuleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page shows the error message.
   */
  public function testDatabaseDriverIsProvidedByModuleButTheModuleIsNotEnabled(): void {
    $driver = Database::getConnection()->driver();
    if (!in_array($driver, ['mysql', 'pgsql'])) {
      $this->markTestSkipped("This test does not support the {$driver} database driver.");
    }

    // Change the default database connection to use the one from the module
    // driver_test.
    $connection_info = Database::getConnectionInfo();
    $database = [
      'database' => $connection_info['default']['database'],
      'username' => $connection_info['default']['username'],
      'password' => $connection_info['default']['password'],
      'prefix' => $connection_info['default']['prefix'],
      'host' => $connection_info['default']['host'],
      'driver' => 'DriverTest' . ucfirst($driver),
      'namespace' => 'Drupal\\driver_test\\Driver\\Database\\DriverTest' . ucfirst($driver),
      'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DriverTest' . ucfirst($driver),
      'dependencies' => [
        $driver => [
          'namespace' => "Drupal\\{$driver}",
          'autoload' => "core/modules/$driver/src/",
        ],
      ],
    ];
    if (isset($connection_info['default']['port'])) {
      $database['port'] = $connection_info['default']['port'];
    }
    $settings['databases']['default']['default'] = (object) [
      'value'    => $database,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    // The module driver_test is not installed and is providing to current
    // database driver. Check that the correct error is shown.
    $this->assertSession()->pageTextContains('Database driver provided by module');
    $this->assertSession()->pageTextContains('The current database driver is provided by the module: driver_test. The module is currently not installed. You should immediately install the module.');
  }

}
