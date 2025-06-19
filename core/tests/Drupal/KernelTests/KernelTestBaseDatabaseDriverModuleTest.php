<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Database\Database;

/**
 * @coversDefaultClass \Drupal\KernelTests\KernelTestBase
 *
 * @group PHPUnit
 * @group Test
 * @group KernelTests
 */
class KernelTestBaseDatabaseDriverModuleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getDatabaseConnectionInfo() {
    // If the test is run with argument SIMPLETEST_DB then use it.
    $db_url = getenv('SIMPLETEST_DB');
    if (empty($db_url)) {
      throw new \Exception('There is no database connection so no tests can be run. You must provide a SIMPLETEST_DB environment variable to run PHPUnit based functional tests outside of run-tests.sh. See https://www.drupal.org/node/2116263#skipped-tests for more information.');
    }
    else {
      $database = Database::convertDbUrlToConnectionInfo($db_url);

      if (in_array($database['driver'], ['mysql', 'pgsql'])) {
        // Change the used database driver to the one provided by the module
        // "driver_test".
        $driver = 'DriverTest' . ucfirst($database['driver']);
        $database['driver'] = $driver;
        $database['namespace'] = 'Drupal\\driver_test\\Driver\\Database\\' . $driver;
        $database['autoload'] = "core/modules/system/tests/modules/driver_test/src/Driver/Database/$driver/";
      }

      Database::addConnectionInfo('default', 'default', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    if (!empty($connection_info)) {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        // Replace the table prefix definition to ensure that no table of the
        // test runner leak into the test.
        $connection_info[$target]['prefix'] = $this->databasePrefix;
      }
    }
    return $connection_info;
  }

  /**
   * @covers ::bootEnvironment
   */
  public function testDatabaseDriverModuleEnabled(): void {
    $driver = Database::getConnection()->driver();
    if (!in_array($driver, ['DriverTestMysql', 'DriverTestPgsql'])) {
      $this->markTestSkipped("This test does not support the {$driver} database driver.");
    }

    // Test that the module that is providing the database driver is enabled.
    $this->assertSame(1, \Drupal::service('extension.list.module')->get('driver_test')->status);
  }

}
