<?php

namespace Drupal\Tests\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Driver\pgsql\Connection;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Database\Driver\pgsql\Connection
 * @group Database
 */
class PostgresqlConnectionTest extends UnitTestCase {

  /**
   * Mock PDO object for use in tests.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Tests\Core\Database\Stub\StubPDO
   */
  protected $mockPdo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mockPdo = $this->getMock('Drupal\Tests\Core\Database\Stub\StubPDO');
  }

  /**
   * Data provider for testEscapeTable.
   *
   * @return []
   *   An indexed array of where each value is an array of arguments to pass to
   *   testEscapeField. The first value is the expected value, and the second
   *   value is the value to test.
   */
  public function providerEscapeTables() {
    return array(
      array('nocase', 'nocase'),
      array('"camelCase"', 'camelCase'),
      array('"camelCase"', '"camelCase"'),
      array('"camelCase"', 'camel/Case'),
    );
  }

  /**
   * Data provider for testEscapeAlias.
   *
   * @return []
   *   Array of arrays with the following elements:
   *   - Expected escaped string.
   *   - String to escape.
   */
  public function providerEscapeAlias() {
    return array(
      array('nocase', 'nocase'),
      array('"camelCase"', '"camelCase"'),
      array('"camelCase"', 'camelCase'),
      array('"camelCase"', 'camel.Case'),
    );
  }

  /**
   * Data provider for testEscapeField.
   *
   * @return []
   *   Array of arrays with the following elements:
   *   - Expected escaped string.
   *   - String to escape.
   */
  public function providerEscapeFields() {
    return array(
      array('title', 'title'),
      array('"isDefaultRevision"', 'isDefaultRevision'),
      array('"isDefaultRevision"', '"isDefaultRevision"'),
      array('entity_test."isDefaultRevision"', 'entity_test.isDefaultRevision'),
      array('entity_test."isDefaultRevision"', '"entity_test"."isDefaultRevision"'),
      array('"entityTest"."isDefaultRevision"', '"entityTest"."isDefaultRevision"'),
      array('"entityTest"."isDefaultRevision"', 'entityTest.isDefaultRevision'),
      array('entity_test."isDefaultRevision"', 'entity_test.is.Default.Revision'),
    );
  }

  /**
   * @covers ::escapeTable
   * @dataProvider providerEscapeTables
   */
  public function testEscapeTable($expected, $name) {
    $pgsql_connection = new Connection($this->mockPdo, []);

    $this->assertEquals($expected, $pgsql_connection->escapeTable($name));
  }

  /**
   * @covers ::escapeAlias
   * @dataProvider providerEscapeAlias
   */
  public function testEscapeAlias($expected, $name) {
    $pgsql_connection = new Connection($this->mockPdo, []);

    $this->assertEquals($expected, $pgsql_connection->escapeAlias($name));
  }

  /**
   * @covers ::escapeField
   * @dataProvider providerEscapeFields
   */
  public function testEscapeField($expected, $name) {
    $pgsql_connection = new Connection($this->mockPdo, []);

    $this->assertEquals($expected, $pgsql_connection->escapeField($name));
  }

}
