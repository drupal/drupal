<?php

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
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
   * Data provider for testPrefixRoundTrip().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Arguments to pass to Connection::setPrefix().
   *   - Expected result from Connection::tablePrefix().
   */
  public function providerPrefixRoundTrip() {
    return [
      [
        [
          '' => 'test_',
        ],
        'test_',
      ],
      [
        [
          'fooTable' => 'foo_',
          'barTable' => 'foo_',
        ],
        'foo_',
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
   * Data provider for testPrefixTables().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected result.
   *   - Table prefix.
   *   - Query to be prefixed.
   *   - Quote identifier.
   */
  public function providerTestPrefixTables() {
    return [
      [
        'SELECT * FROM test_table',
        'test_',
        'SELECT * FROM {table}',
        ['', ''],
      ],
      [
        'SELECT * FROM "test_table"',
        'test_',
        'SELECT * FROM {table}',
        ['"', '"'],
      ],
      [
        "SELECT * FROM 'test_table'",
        'test_',
        'SELECT * FROM {table}',
        ["'", "'"],
      ],
      [
        "SELECT * FROM [test_table]",
        'test_',
        'SELECT * FROM {table}',
        ['[', ']'],
      ],
    ];
  }

  /**
   * Exercise the prefixTables() method.
   *
   * @dataProvider providerTestPrefixTables
   */
  public function testPrefixTables($expected, $prefix_info, $query, array $quote_identifier = ['"', '"']) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, ['prefix' => $prefix_info], $quote_identifier);
    $this->assertEquals($expected, $connection->prefixTables($query));
  }

  /**
   * Data provider for testGetDriverClass().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
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
      // Tests with the corefake database driver. This driver has no custom
      // driver classes.
      [
        'Drupal\Core\Database\Query\Condition',
        'Drupal\corefake\Driver\Database\corefake',
        'Condition',
      ],
      [
        'Drupal\Core\Database\Query\Delete',
        'Drupal\corefake\Driver\Database\corefake',
        'Delete',
      ],
      [
        'Drupal\Core\Database\ExceptionHandler',
        'Drupal\corefake\Driver\Database\corefake',
        'ExceptionHandler',
      ],
      [
        'Drupal\Core\Database\Query\Insert',
        'Drupal\corefake\Driver\Database\corefake',
        'Insert',
      ],
      [
        'Drupal\Core\Database\Query\Merge',
        'Drupal\corefake\Driver\Database\corefake',
        'Merge',
      ],
      [
        'PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'PagerSelectExtender',
      ],
      [
        'Drupal\Core\Database\Schema',
        'Drupal\corefake\Driver\Database\corefake',
        'Schema',
      ],
      [
        'SearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        'SearchQuery',
      ],
      [
        'Drupal\Core\Database\Query\Select',
        'Drupal\corefake\Driver\Database\corefake',
        'Select',
      ],
      [
        'Drupal\Core\Database\Transaction',
        'Drupal\corefake\Driver\Database\corefake',
        'Transaction',
      ],
      [
        'TableSortExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'TableSortExtender',
      ],
      [
        'Drupal\Core\Database\Query\Truncate',
        'Drupal\corefake\Driver\Database\corefake',
        'Truncate',
      ],
      [
        'Drupal\Core\Database\Query\Update',
        'Drupal\corefake\Driver\Database\corefake',
        'Update',
      ],
      [
        'Drupal\Core\Database\Query\Upsert',
        'Drupal\corefake\Driver\Database\corefake',
        'Upsert',
      ],
      // Tests with the corefakeWithAllCustomClasses database driver. This
      // driver has custom driver classes for all classes.
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Condition',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Condition',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Delete',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Delete',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\ExceptionHandler',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'ExceptionHandler',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Insert',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Insert',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Merge',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Merge',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'PagerSelectExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Schema',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Schema',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\SearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'SearchQuery',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Select',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Select',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'TableSortExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Transaction',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Transaction',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Truncate',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Truncate',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Update',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Update',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\Upsert',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Upsert',
      ],
      [
        'Drupal\Core\Database\Query\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        '\Drupal\Core\Database\Query\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        'Drupal\Core\Database\Query\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        '\Drupal\Core\Database\Query\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\search\SearchQuery',
      ],
      [
        '\Drupal\search\SearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\search\SearchQuery',
      ],
    ];
  }

  /**
   * @covers ::getDriverClass
   * @dataProvider providerGetDriverClass
   */
  public function testGetDriverClass($expected, $namespace, $class) {
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\corefake\\Driver\\Database\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/corefake/src/Driver/Database/corefake");
    $additional_class_loader->addPsr4("Drupal\\corefake\\Driver\\Database\\corefakeWithAllCustomClasses\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/corefake/src/Driver/Database/corefakeWithAllCustomClasses");
    $additional_class_loader->register(TRUE);

    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, ['namespace' => $namespace]);
    $this->assertEquals($expected, $connection->getDriverClass($class));
  }

  /**
   * Data provider for testSchema().
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
   * Tests Connection::schema().
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
   * Data provider for testMakeComments().
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
   * Tests Connection::makeComments().
   *
   * @dataProvider providerMakeComments
   */
  public function testMakeComments($expected, $comment_array) {
    $mock_pdo = $this->createMock('Drupal\Tests\Core\Database\Stub\StubPDO');
    $connection = new StubConnection($mock_pdo, []);
    $this->assertEquals($expected, $connection->makeComment($comment_array));
  }

  /**
   * Data provider for testFilterComments().
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
   * Tests Connection::filterComments().
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
      ['backtick', '`backtick`', ['`', '`']],
      ['brackets', '[brackets]', ['[', ']']],
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
  public function testEscapeTable($expected, $name, array $identifier_quote = ['"', '"']) {
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
      ['!nocase!', 'nocase', ['!', '!']],
      ['`backtick`', 'backtick', ['`', '`']],
      ['nocase', 'nocase', ['', '']],
      ['[brackets]', 'brackets', ['[', ']']],
      ['"camelCase"', '"camelCase"'],
      ['"camelCase"', 'camelCase'],
      ['"camelCase"', 'camel.Case'],
    ];
  }

  /**
   * @covers ::escapeAlias
   * @dataProvider providerEscapeAlias
   */
  public function testEscapeAlias($expected, $name, array $identifier_quote = ['"', '"']) {
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
      ['/title/', 'title', ['/', '/']],
      ['`backtick`', 'backtick', ['`', '`']],
      ['test.title', 'test.title', ['', '']],
      ['"isDefaultRevision"', 'isDefaultRevision'],
      ['"isDefaultRevision"', '"isDefaultRevision"'],
      ['"entity_test"."isDefaultRevision"', 'entity_test.isDefaultRevision'],
      ['"entity_test"."isDefaultRevision"', '"entity_test"."isDefaultRevision"'],
      ['"entityTest"."isDefaultRevision"', '"entityTest"."isDefaultRevision"'],
      ['"entityTest"."isDefaultRevision"', 'entityTest.isDefaultRevision'],
      ['[entityTest].[isDefaultRevision]', 'entityTest.isDefaultRevision', ['[', ']']],
    ];
  }

  /**
   * @covers ::escapeField
   * @dataProvider providerEscapeFields
   */
  public function testEscapeField($expected, $name, array $identifier_quote = ['"', '"']) {
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
      ['/name/', 'name', ['/', '/']],
      ['`backtick`', 'backtick', ['`', '`']],
      ['testname', 'test.name', ['', '']],
      ['"name"', 'name'],
      ['[name]', 'name', ['[', ']']],
    ];
  }

  /**
   * @covers ::escapeDatabase
   * @dataProvider providerEscapeDatabase
   */
  public function testEscapeDatabase($expected, $name, array $identifier_quote = ['"', '"']) {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, [], $identifier_quote);

    $this->assertEquals($expected, $connection->escapeDatabase($name));
  }

  /**
   * @covers ::__construct
   */
  public function testIdentifierQuotesAssertCount() {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('\Drupal\Core\Database\Connection::$identifierQuotes must contain 2 string values');
    $mock_pdo = $this->createMock(StubPDO::class);
    new StubConnection($mock_pdo, [], ['"']);
  }

  /**
   * @covers ::__construct
   */
  public function testIdentifierQuotesAssertString() {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('\Drupal\Core\Database\Connection::$identifierQuotes must contain 2 string values');
    $mock_pdo = $this->createMock(StubPDO::class);
    new StubConnection($mock_pdo, [], [0, '1']);
  }

  /**
   * @covers ::__construct
   */
  public function testNamespaceDefault() {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, []);
    $this->assertSame('Drupal\Tests\Core\Database\Stub', $connection->getConnectionOptions()['namespace']);
  }

  /**
   * Test rtrim() of query strings.
   *
   * @dataProvider provideQueriesToTrim
   */
  public function testQueryTrim($expected, $query, $options) {
    $mock_pdo = $this->getMockBuilder(StubPdo::class)->getMock();
    $connection = new StubConnection($mock_pdo, []);

    $preprocess_method = new \ReflectionMethod($connection, 'preprocessStatement');
    $preprocess_method->setAccessible(TRUE);
    $this->assertSame($expected, $preprocess_method->invoke($connection, $query, $options));
  }

  /**
   * Data provider for testQueryTrim().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected trimmed query.
   *   - Padded query.
   *   - Query options.
   */
  public function provideQueriesToTrim() {
    return [
      'remove_non_breaking_space' => [
        'SELECT * FROM test',
        "SELECT * FROM test\xA0",
        [],
      ],
      'remove_ordinary_space' => [
        'SELECT * FROM test',
        'SELECT * FROM test ',
        [],
      ],
      'remove_semicolon' => [
        'SELECT * FROM test',
        'SELECT * FROM test;',
        [],
      ],
      'keep_trailing_semicolon' => [
        'SELECT * FROM test;',
        'SELECT * FROM test;',
        ['allow_delimiter_in_query' => TRUE],
      ],
      'remove_semicolon_with_whitespace' => [
        'SELECT * FROM test',
        'SELECT * FROM test; ',
        [],
      ],
      'keep_trailing_semicolon_with_whitespace' => [
        'SELECT * FROM test;',
        'SELECT * FROM test; ',
        ['allow_delimiter_in_query' => TRUE],
      ],
    ];
  }

}
