<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for database URL to/from database connection array coversions.
 *
 * These tests run in isolation since we don't want the database static to
 * affect other tests.
 *
 * @coversDefaultClass \Drupal\Core\Database\Database
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group Database
 */
class UrlConversionTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->root = dirname(__FILE__, 7);
    // Mock the container so we don't need to mock drupal_valid_test_ua().
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('has')
      ->with('kernel')
      ->willReturn(TRUE);
    $container->expects($this->any())
      ->method('getParameter')
      ->with('site.path')
      ->willReturn('');
    \Drupal::setContainer($container);

    new Settings(['extension_discovery_scan_tests' => TRUE]);
  }

  /**
   * @covers ::convertDbUrlToConnectionInfo
   *
   * @dataProvider providerConvertDbUrlToConnectionInfo
   */
  public function testDbUrltoConnectionConversion($root, $url, $database_array) {
    $result = Database::convertDbUrlToConnectionInfo($url, $root ?: $this->root);
    $this->assertEquals($database_array, $result);
  }

  /**
   * Dataprovider for testDbUrltoConnectionConversion().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - root: The baseroot string, only used with sqlite drivers.
   *   - url: The full URL string to be tested.
   *   - database_array: An array containing the expected results.
   */
  public function providerConvertDbUrlToConnectionInfo() {
    return [
      'MySql without prefix' => [
        '',
        'mysql://test_user:test_pass@test_host:3306/test_database',
        [
          'driver' => 'mysql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'port' => 3306,
          'namespace' => 'Drupal\Core\Database\Driver\mysql',
        ],
      ],
      'SQLite, relative to root, without prefix' => [
        '/var/www/d8',
        'sqlite://localhost/test_database',
        [
          'driver' => 'sqlite',
          'host' => 'localhost',
          'database' => '/var/www/d8/test_database',
          'namespace' => 'Drupal\Core\Database\Driver\sqlite',
        ],
      ],
      'MySql with prefix' => [
        '',
        'mysql://test_user:test_pass@test_host:3306/test_database#bar',
        [
          'driver' => 'mysql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'prefix' => [
            'default' => 'bar',
          ],
          'port' => 3306,
          'namespace' => 'Drupal\Core\Database\Driver\mysql',
        ],
      ],
      'SQLite, relative to root, with prefix' => [
        '/var/www/d8',
        'sqlite://localhost/test_database#foo',
        [
          'driver' => 'sqlite',
          'host' => 'localhost',
          'database' => '/var/www/d8/test_database',
          'prefix' => [
            'default' => 'foo',
          ],
          'namespace' => 'Drupal\Core\Database\Driver\sqlite',
        ],
      ],
      'SQLite, absolute path, without prefix' => [
        '/var/www/d8',
        'sqlite://localhost//baz/test_database',
        [
          'driver' => 'sqlite',
          'host' => 'localhost',
          'database' => '/baz/test_database',
          'namespace' => 'Drupal\Core\Database\Driver\sqlite',
        ],
      ],
      'MySQL contrib test driver without prefix' => [
        '',
        'DrivertestMysql://test_user:test_pass@test_host:3306/test_database?module=driver_test',
        [
          'driver' => 'DrivertestMysql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'port' => 3306,
          'namespace' => 'Drupal\driver_test\Driver\Database\DrivertestMysql',
          'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/',
        ],
      ],
      'MySQL contrib test driver with prefix' => [
        '',
        'DrivertestMysql://test_user:test_pass@test_host:3306/test_database?module=driver_test#bar',
        [
          'driver' => 'DrivertestMysql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'prefix' => [
            'default' => 'bar',
          ],
          'port' => 3306,
          'namespace' => 'Drupal\driver_test\Driver\Database\DrivertestMysql',
          'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/',
        ],
      ],
      'PostgreSQL contrib test driver without prefix' => [
        '',
        'DrivertestPgsql://test_user:test_pass@test_host:5432/test_database?module=driver_test',
        [
          'driver' => 'DrivertestPgsql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'port' => 5432,
          'namespace' => 'Drupal\driver_test\Driver\Database\DrivertestPgsql',
          'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestPgsql/',
        ],
      ],
      'PostgreSQL contrib test driver with prefix' => [
        '',
        'DrivertestPgsql://test_user:test_pass@test_host:5432/test_database?module=driver_test#bar',
        [
          'driver' => 'DrivertestPgsql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'prefix' => [
            'default' => 'bar',
          ],
          'port' => 5432,
          'namespace' => 'Drupal\driver_test\Driver\Database\DrivertestPgsql',
          'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestPgsql/',
        ],
      ],
      'MySql with a custom query parameter' => [
        '',
        'mysql://test_user:test_pass@test_host:3306/test_database?extra=value',
        [
          'driver' => 'mysql',
          'username' => 'test_user',
          'password' => 'test_pass',
          'host' => 'test_host',
          'database' => 'test_database',
          'port' => 3306,
          'namespace' => 'Drupal\Core\Database\Driver\mysql',
        ],
      ],
    ];
  }

  /**
   * Test ::convertDbUrlToConnectionInfo() exception for invalid arguments.
   *
   * @dataProvider providerInvalidArgumentsUrlConversion
   */
  public function testGetInvalidArgumentExceptionInUrlConversion($url, $root, $expected_exception_message) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($expected_exception_message);
    Database::convertDbUrlToConnectionInfo($url, $root);
  }

  /**
   * Dataprovider for testGetInvalidArgumentExceptionInUrlConversion().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - An invalid Url string.
   *   - Drupal root string.
   *   - The expected exception message.
   */
  public function providerInvalidArgumentsUrlConversion() {
    return [
      ['foo', '', "Missing scheme in URL 'foo'"],
      ['foo', 'bar', "Missing scheme in URL 'foo'"],
      ['foo://', 'bar', "Can not convert 'foo://' to a database connection, class 'Drupal\\Driver\\Database\\foo\\Connection' does not exist"],
      ['foo://bar', 'baz', "Can not convert 'foo://bar' to a database connection, class 'Drupal\\Driver\\Database\\foo\\Connection' does not exist"],
      ['foo://bar:port', 'baz', "Can not convert 'foo://bar:port' to a database connection, class 'Drupal\\Driver\\Database\\foo\\Connection' does not exist"],
      ['foo/bar/baz', 'bar2', "Missing scheme in URL 'foo/bar/baz'"],
      ['foo://bar:baz@test1', 'test2', "Can not convert 'foo://bar:baz@test1' to a database connection, class 'Drupal\\Driver\\Database\\foo\\Connection' does not exist"],
    ];
  }

  /**
   * @covers ::getConnectionInfoAsUrl
   *
   * @dataProvider providerGetConnectionInfoAsUrl
   */
  public function testGetConnectionInfoAsUrl(array $info, $expected_url) {
    Database::addConnectionInfo('default', 'default', $info);
    $url = Database::getConnectionInfoAsUrl();
    $this->assertEquals($expected_url, $url);
  }

  /**
   * Dataprovider for testGetConnectionInfoAsUrl().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - An array mocking the database connection info. Possible keys are
   *     database, username, password, prefix, host, port, namespace and driver.
   *   - The expected URL after conversion.
   */
  public function providerGetConnectionInfoAsUrl() {
    $info1 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => '',
      'host' => 'test_host',
      'port' => '3306',
      'driver' => 'mysql',
    ];
    $expected_url1 = 'mysql://test_user:test_pass@test_host:3306/test_database';

    $info2 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => 'pre',
      'host' => 'test_host',
      'port' => '3306',
      'driver' => 'mysql',
    ];
    $expected_url2 = 'mysql://test_user:test_pass@test_host:3306/test_database#pre';

    $info3 = [
      'database' => 'test_database',
      'driver' => 'sqlite',
    ];
    $expected_url3 = 'sqlite://localhost/test_database';

    $info4 = [
      'database' => 'test_database',
      'driver' => 'sqlite',
      'prefix' => 'pre',
    ];
    $expected_url4 = 'sqlite://localhost/test_database#pre';

    $info5 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => '',
      'host' => 'test_host',
      'port' => '3306',
      'driver' => 'DrivertestMysql',
      'namespace' => 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysql',
      'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/',
    ];
    $expected_url5 = 'DrivertestMysql://test_user:test_pass@test_host:3306/test_database?module=driver_test';

    $info6 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => 'pre',
      'host' => 'test_host',
      'port' => '3306',
      'driver' => 'DrivertestMysql',
      'namespace' => 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysql',
      'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/DrivertestMysql/',
    ];
    $expected_url6 = 'DrivertestMysql://test_user:test_pass@test_host:3306/test_database?module=driver_test#pre';

    $info7 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => '',
      'host' => 'test_host',
      'port' => '5432',
      'driver' => 'DrivertestPgsql',
      'namespace' => 'Drupal\\driver_test\\Driver\\Database\\DrivertestPgsql',
      'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/drivertestpqsql/',
    ];
    $expected_url7 = 'DrivertestPgsql://test_user:test_pass@test_host:5432/test_database?module=driver_test';

    $info8 = [
      'database' => 'test_database',
      'username' => 'test_user',
      'password' => 'test_pass',
      'prefix' => 'pre',
      'host' => 'test_host',
      'port' => '5432',
      'driver' => 'DrivertestPgsql',
      'namespace' => 'Drupal\\driver_test\\Driver\\Database\\DrivertestPgsql',
      'autoload' => 'core/modules/system/tests/modules/driver_test/src/Driver/Database/drivertestpqsql/',
    ];
    $expected_url8 = 'DrivertestPgsql://test_user:test_pass@test_host:5432/test_database?module=driver_test#pre';

    return [
      [$info1, $expected_url1],
      [$info2, $expected_url2],
      [$info3, $expected_url3],
      [$info4, $expected_url4],
      [$info5, $expected_url5],
      [$info6, $expected_url6],
      [$info7, $expected_url7],
      [$info8, $expected_url8],
    ];
  }

  /**
   * Test ::getConnectionInfoAsUrl() exception for invalid arguments.
   *
   * @covers ::getConnectionInfoAsUrl
   *
   * @param array $connection_options
   *   The database connection information.
   * @param string $expected_exception_message
   *   The expected exception message.
   *
   * @dataProvider providerInvalidArgumentGetConnectionInfoAsUrl
   */
  public function testGetInvalidArgumentGetConnectionInfoAsUrl(array $connection_options, $expected_exception_message) {
    Database::addConnectionInfo('default', 'default', $connection_options);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $url = Database::getConnectionInfoAsUrl();
  }

  /**
   * Dataprovider for testGetInvalidArgumentGetConnectionInfoAsUrl().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - An array mocking the database connection info. Possible keys are
   *     database, username, password, prefix, host, port, namespace and driver.
   *   - The expected exception message.
   */
  public function providerInvalidArgumentGetConnectionInfoAsUrl() {
    return [
      'Missing database key' => [
        [
          'driver' => 'sqlite',
          'host' => 'localhost',
          'namespace' => 'Drupal\Core\Database\Driver\sqlite',
        ],
        "As a minimum, the connection options array must contain at least the 'driver' and 'database' keys",
      ],
    ];
  }

  /**
   * @covers ::convertDbUrlToConnectionInfo
   */
  public function testDriverModuleDoesNotExist() {
    $url = 'mysql://test_user:test_pass@test_host:3306/test_database?module=does_not_exist';
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Cannot find the module 'does_not_exist' for the database driver namespace 'Drupal\does_not_exist\Driver\Database\mysql'");
    Database::convertDbUrlToConnectionInfo($url, $this->root);
  }

  /**
   * @covers ::convertDbUrlToConnectionInfo
   */
  public function testModuleDriverDoesNotExist() {
    $url = 'mysql://test_user:test_pass@test_host:3306/test_database?module=driver_test';
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Cannot find the database driver namespace 'Drupal\driver_test\Driver\Database\mysql' in module 'driver_test'");
    Database::convertDbUrlToConnectionInfo($url, $this->root);
  }

}
