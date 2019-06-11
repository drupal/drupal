<?php

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Database;
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
  protected function setUp() {
    parent::setUp();
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\Driver\\Database\\fake\\", __DIR__ . "/fixtures/driver/fake");
    $additional_class_loader->register(TRUE);
  }

  /**
   * @covers ::convertDbUrlToConnectionInfo
   *
   * @dataProvider providerConvertDbUrlToConnectionInfo
   */
  public function testDbUrltoConnectionConversion($root, $url, $database_array) {
    $result = Database::convertDbUrlToConnectionInfo($url, $root);
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
      'Fake custom database driver, without prefix' => [
        '',
        'fake://fake_user:fake_pass@fake_host:3456/fake_database',
        [
          'driver' => 'fake',
          'username' => 'fake_user',
          'password' => 'fake_pass',
          'host' => 'fake_host',
          'database' => 'fake_database',
          'port' => 3456,
          'namespace' => 'Drupal\Driver\Database\fake',
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

    return [
      [$info1, $expected_url1],
      [$info2, $expected_url2],
      [$info3, $expected_url3],
      [$info4, $expected_url4],
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

}
