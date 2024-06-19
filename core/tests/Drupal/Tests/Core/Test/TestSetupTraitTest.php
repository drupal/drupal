<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestSetupTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TestSetupTrait trait.
 *
 * Run in a separate process as this test involves Database statics and
 * environment variables.
 *
 * @coversDefaultClass \Drupal\Core\Test\TestSetupTrait
 * @group Testing
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TestSetupTraitTest extends UnitTestCase {

  /**
   * Tests the SIMPLETEST_DB environment variable is used.
   *
   * @covers ::changeDatabasePrefix
   */
  public function testChangeDatabasePrefix(): void {
    $root = dirname(__FILE__, 7);
    putenv('SIMPLETEST_DB=pgsql://user:pass@127.0.0.1/db');
    $connection_info = Database::convertDbUrlToConnectionInfo('mysql://user:pass@localhost/db', $root);
    Database::addConnectionInfo('default', 'default', $connection_info);
    $this->assertEquals('mysql', Database::getConnectionInfo()['default']['driver']);
    $this->assertEquals('localhost', Database::getConnectionInfo()['default']['host']);

    // Create a mock for testing the trait and set a few properties that are
    // used to avoid unnecessary set up.
    $test_setup = new class() {

      use TestSetupTrait;

    };

    $reflection = new \ReflectionClass($test_setup);
    $reflection->getProperty('databasePrefix')->setValue($test_setup, 'testDbPrefix');
    $reflection->getProperty('root')->setValue($test_setup, $root);

    $method = new \ReflectionMethod(get_class($test_setup), 'changeDatabasePrefix');
    $method->invoke($test_setup);

    // Ensure that SIMPLETEST_DB defines the default database connection after
    // calling \Drupal\Core\Test\TestSetupTrait::changeDatabasePrefix().
    $this->assertEquals('pgsql', Database::getConnectionInfo()['default']['driver']);
    $this->assertEquals('127.0.0.1', Database::getConnectionInfo()['default']['host']);
  }

}
