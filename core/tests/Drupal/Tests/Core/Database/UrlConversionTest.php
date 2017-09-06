<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Database\Database
 *
 * @group Database
 */
class UrlConversionTest extends UnitTestCase {

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
    // Some valid datasets.
    $root1 = '';
    $url1 = 'mysql://test_user:test_pass@test_host:3306/test_database';
    $database_array1 = [
      'driver' => 'mysql',
      'username' => 'test_user',
      'password' => 'test_pass',
      'host' => 'test_host',
      'database' => 'test_database',
      'port' => '3306',
    ];
    $root2 = '/var/www/d8';
    $url2 = 'sqlite://test_user:test_pass@test_host:3306/test_database';
    $database_array2 = [
      'driver' => 'sqlite',
      'username' => 'test_user',
      'password' => 'test_pass',
      'host' => 'test_host',
      'database' => $root2 . '/test_database',
      'port' => 3306,
    ];
    return [
      [$root1, $url1, $database_array1],
      [$root2, $url2, $database_array2],
    ];
  }

  /**
   * Test ::convertDbUrlToConnectionInfo() exception for invalid arguments.
   *
   * @dataProvider providerInvalidArgumentsUrlConversion
   */
  public function testGetInvalidArgumentExceptionInUrlConversion($url, $root) {
    $this->setExpectedException(\InvalidArgumentException::class);
    Database::convertDbUrlToConnectionInfo($url, $root);
  }

  /**
   * Dataprovider for testGetInvalidArgumentExceptionInUrlConversion().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - An invalid Url string.
   *   - Drupal root string.
   */
  public function providerInvalidArgumentsUrlConversion() {
    return [
      ['foo', ''],
      ['foo', 'bar'],
      ['foo://', 'bar'],
      ['foo://bar', 'baz'],
      ['foo://bar:port', 'baz'],
      ['foo/bar/baz', 'bar2'],
      ['foo://bar:baz@test1', 'test2'],
    ];
  }

  /**
   * @covers ::convertDbUrlToConnectionInfo
   *
   * @dataProvider providerGetConnectionInfoAsUrl
   */
  public function testGetConnectionInfoAsUrl(array $info, $expected_url) {

    Database::addConnectionInfo('default', 'default', $info);
    $url = Database::getConnectionInfoAsUrl();

    // Remove the connection to not pollute subsequent datasets being tested.
    Database::removeConnection('default');

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
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
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

    return [
      [$info1, $expected_url1],
      [$info2, $expected_url2],
      [$info3, $expected_url3],
    ];
  }

}
