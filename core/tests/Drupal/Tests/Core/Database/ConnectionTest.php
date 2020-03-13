<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Connection class.
 *
 * @coversDefaultClass \Drupal\Core\Database\Connection
 * @group Database
 */
class ConnectionTest extends UnitTestCase {

  /**
   * Dataprovider for testPrefixRoundTrip().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Arguments to pass to Connection::setPrefix().
   *   - Expected result from Connection::tablePrefix().
   */
  public function providerPrefixRoundTrip() {
    return [
      [
        ['' => 'test_'],
        'test_',
      ],
      [
        [
          'fooTable' => 'foo_',
          'barTable' => 'bar_',
        ],
        [
          'fooTable' => 'foo_',
          'barTable' => 'bar_',
        ],
      ],
    ];
  }

  /**
   * Exercise setPrefix() and tablePrefix().
   *
   * @dataProvider providerPrefixRoundTrip
   */
  public function testPrefixRoundTrip($expected, $prefix_info) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, []);

    // setPrefix() is protected, so we make it accessible with reflection.
    $reflection = new \ReflectionClass('Drupal\Tests\Core\Database\Stub\StubConnection');
    $set_prefix = $reflection->getMethod('setPrefix');
    $set_prefix->setAccessible(TRUE);

    // Set the prefix data.
    $set_prefix->invokeArgs($connection, [$prefix_info]);
    // Check the round-trip.
    foreach ($expected as $table => $prefix) {
      $this->assertEquals($prefix, $connection->tablePrefix($table));
    }
  }

  /**
   * Dataprovider for testPrefixTables().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected result.
   *   - Table prefix.
   *   - Query to be prefixed.
   */
  public function providerTestPrefixTables() {
    return [
      [
        'SELECT * FROM test_table',
        'test_',
        'SELECT * FROM {table}',
        '',
      ],
      [
        'SELECT * FROM "first_table" JOIN "second"."thingie"',
        [
          'table' => 'first_',
          'thingie' => 'second.',
        ],
        'SELECT * FROM {table} JOIN {thingie}',
      ],
    ];
  }

  /**
   * Exercise the prefixTables() method.
   *
   * @dataProvider providerTestPrefixTables
   */
  public function testPrefixTables($expected, $prefix_info, $query, $quote_identifier = '"') {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, ['prefix' => $prefix_info], $quote_identifier);
    $this->assertEquals($expected, $connection->prefixTables($query));
  }

  /**
   * Dataprovider for testGetDriverClass().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
   *   - Driver.
   *   - Namespace.
   *   - Class name without namespace.
   */
  public function providerGetDriverClass() {
    return [
      [
        'nonexistent_class',
        '\\',
        'nonexistent_class',
      ],
      [
        'Drupal\Tests\Core\Database\Stub\Select',
        NULL,
        'Select',
      ],
      [
        'Drupal\\Tests\\Core\\Database\\Stub\\Driver\\Schema',
        'Drupal\\Tests\\Core\\Database\\Stub\\Driver',
        'Schema',
      ],
    ];
  }

  /**
   * Test getDriverClass().
   *
   * @dataProvider providerGetDriverClass
   */
  public function testGetDriverClass($expected, $namespace, $class) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, ['namespace' => $namespace]);
    // Set the driver using our stub class' public property.
    $this->assertEquals($expected, $connection->getDriverClass($class));
  }

  /**
   * Dataprovider for testSchema().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class of schema object.
   *   - Driver for PDO connection.
   *   - Namespace for connection.
   */
  public function providerSchema() {
    return [
      [
        'Drupal\\Tests\\Core\\Database\\Stub\\Driver\\Schema',
        'stub',
        'Drupal\\Tests\\Core\\Database\\Stub\\Driver',
      ],
    ];
  }

  /**
   * Test Connection::schema().
   *
   * @dataProvider providerSchema
   */
  public function testSchema($expected, $driver, $namespace) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, ['namespace' => $namespace]);
    $connection->driver = $driver;
    $this->assertInstanceOf($expected, $connection->schema());
  }

  /**
   * Test Connection::destroy().
   */
  public function testDestroy() {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    // Mocking StubConnection gives us access to the $schema attribute.
    $connection = new StubConnection($mock_pdo, ['namespace' => 'Drupal\\Tests\\Core\\Database\\Stub\\Driver']);
    // Generate a schema object in order to verify that we've NULLed it later.
    $this->assertInstanceOf(
      'Drupal\\Tests\\Core\\Database\\Stub\\Driver\\Schema',
      $connection->schema()
    );
    $connection->destroy();
    $this->assertAttributeEquals(NULL, 'schema', $connection);
  }

  /**
   * Dataprovider for testMakeComments().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected filtered comment.
   *   - Arguments for Connection::makeComment().
   */
  public function providerMakeComments() {
    return [
      [
        '/*  */ ',
        [''],
      ],
      [
        '/* Exploit  *  / DROP TABLE node. -- */ ',
        ['Exploit * / DROP TABLE node; --'],
      ],
      [
        '/* Exploit  *  / DROP TABLE node. --. another comment */ ',
        ['Exploit * / DROP TABLE node; --', 'another comment'],
      ],
    ];
  }

  /**
   * Test Connection::makeComments().
   *
   * @dataProvider providerMakeComments
   */
  public function testMakeComments($expected, $comment_array) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, []);
    $this->assertEquals($expected, $connection->makeComment($comment_array));
  }

  /**
   * Dataprovider for testFilterComments().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected filtered comment.
   *   - Comment to filter.
   */
  public function providerFilterComments() {
    return [
      ['', ''],
      ['Exploit  *  / DROP TABLE node. --', 'Exploit * / DROP TABLE node; --'],
      ['Exploit  * / DROP TABLE node. --', 'Exploit */ DROP TABLE node; --'],
    ];
  }

  /**
   * Test Connection::filterComments().
   *
   * @dataProvider providerFilterComments
   */
  public function testFilterComments($expected, $comment) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, []);

    // filterComment() is protected, so we make it accessible with reflection.
    $reflection = new \ReflectionClass('Drupal\Tests\Core\Database\Stub\StubConnection');
    $filter_comment = $reflection->getMethod('filterComment');
    $filter_comment->setAccessible(TRUE);

    $this->assertEquals(
      $expected,
      $filter_comment->invokeArgs($connection, [$comment])
    );
  }

  /**
   * Data provider for testEscapeTable.
   *
   * @return array
   *   An indexed array of where each value is an array of arguments to pass to
   *   testEscapeField. The first value is the expected value, and the second
   *   value is the value to test.
   */
  public function providerEscapeTables() {
    return [
      ['nocase', 'nocase'],
      ['camelCase', 'camelCase'],
      ['backtick', '`backtick`', '`'],
      ['camelCase', '"camelCase"'],
      ['camelCase', 'camel/Case'],
      // Sometimes, table names are following the pattern database.schema.table.
      ['camelCase.nocase.nocase', 'camelCase.nocase.nocase'],
      ['nocase.camelCase.nocase', 'nocase.camelCase.nocase'],
      ['nocase.nocase.camelCase', 'nocase.nocase.camelCase'],
      ['camelCase.camelCase.camelCase', 'camelCase.camelCase.camelCase'],
    ];
  }

  /**
   * @covers ::escapeTable
   * @dataProvider providerEscapeTables
   */
  public function testEscapeTable($expected, $name, $identifier_quote = '"') {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, [], $identifier_quote);

    $this->assertEquals($expected, $connection->escapeTable($name));
  }

  /**
   * Data provider for testEscapeAlias.
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected escaped string.
   *   - String to escape.
   */
  public function providerEscapeAlias() {
    return [
      ['!nocase!', 'nocase', '!'],
      ['`backtick`', 'backtick', '`'],
      ['nocase', 'nocase', ''],
      ['"camelCase"', '"camelCase"'],
      ['"camelCase"', 'camelCase'],
      ['"camelCase"', 'camel.Case'],
    ];
  }

  /**
   * @covers ::escapeAlias
   * @dataProvider providerEscapeAlias
   */
  public function testEscapeAlias($expected, $name, $identifier_quote = '"') {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, [], $identifier_quote);

    $this->assertEquals($expected, $connection->escapeAlias($name));
  }

  /**
   * Data provider for testEscapeField.
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected escaped string.
   *   - String to escape.
   */
  public function providerEscapeFields() {
    return [
      ['/title/', 'title', '/'],
      ['`backtick`', 'backtick', '`'],
      ['test.title', 'test.title', ''],
      ['"isDefaultRevision"', 'isDefaultRevision'],
      ['"isDefaultRevision"', '"isDefaultRevision"'],
      ['"entity_test"."isDefaultRevision"', 'entity_test.isDefaultRevision'],
      ['"entity_test"."isDefaultRevision"', '"entity_test"."isDefaultRevision"'],
      ['"entityTest"."isDefaultRevision"', '"entityTest"."isDefaultRevision"'],
      ['"entityTest"."isDefaultRevision"', 'entityTest.isDefaultRevision'],
    ];
  }

  /**
   * @covers ::escapeField
   * @dataProvider providerEscapeFields
   */
  public function testEscapeField($expected, $name, $identifier_quote = '"') {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, [], $identifier_quote);

    $this->assertEquals($expected, $connection->escapeField($name));
  }

  /**
   * Data provider for testEscapeDatabase.
   *
   * @return array
   *   An indexed array of where each value is an array of arguments to pass to
   *   testEscapeField. The first value is the expected value, and the second
   *   value is the value to test.
   */
  public function providerEscapeDatabase() {
    return [
      ['/name/', 'name', '/'],
      ['`backtick`', 'backtick', '`'],
      ['testname', 'test.name', ''],
      ['"name"', 'name'],
    ];
  }

  /**
   * @covers ::escapeDatabase
   * @dataProvider providerEscapeDatabase
   */
  public function testEscapeDatabase($expected, $name, $identifier_quote = '"') {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, [], $identifier_quote);

    $this->assertEquals($expected, $connection->escapeDatabase($name));
  }

}
