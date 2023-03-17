<?php

namespace Drupal\Tests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\StatementPrefetch;
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
      $this->assertEquals($prefix, $connection->getPrefix());
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

  /**
   * Tests that the proper caller is retrieved from the backtrace.
   *
   * @covers ::findCallerFromDebugBacktrace
   * @covers ::removeDatabaseEntriesFromDebugBacktrace
   * @covers ::getDebugBacktrace
   */
  public function testFindCallerFromDebugBacktrace() {
    Database::addConnectionInfo('default', 'default', [
      'driver' => 'test',
      'namespace' => 'Drupal\Tests\Core\Database\Stub',
    ]);
    $connection = new StubConnection($this->createMock(StubPDO::class), []);
    $result = $connection->findCallerFromDebugBacktrace();
    $this->assertSame([
      'file' => __FILE__,
      'line' => 663,
      'function' => 'testFindCallerFromDebugBacktrace',
      'class' => 'Drupal\Tests\Core\Database\ConnectionTest',
      'type' => '->',
      'args' => [],
    ], $result);
  }

  /**
   * Tests that a log called by a custom database driver returns proper caller.
   *
   * @param string $driver_namespace
   *   The driver namespace to be tested.
   * @param array $stack
   *   A test debug_backtrace stack.
   * @param array $expected_entry
   *   The expected stack entry.
   *
   * @covers ::findCallerFromDebugBacktrace
   * @covers ::removeDatabaseEntriesFromDebugBacktrace
   *
   * @dataProvider providerMockedBacktrace
   *
   * @group legacy
   */
  public function testFindCallerFromDebugBacktraceWithMockedBacktrace(string $driver_namespace, array $stack, array $expected_entry): void {
    $mock_builder = $this->getMockBuilder(StubConnection::class);
    $connection = $mock_builder
      ->onlyMethods(['getDebugBacktrace', 'getConnectionOptions'])
      ->setConstructorArgs([$this->createMock(StubPDO::class), []])
      ->getMock();
    $connection->expects($this->once())
      ->method('getConnectionOptions')
      ->willReturn([
        'driver' => 'test',
        'namespace' => $driver_namespace,
      ]);
    $connection->expects($this->once())
      ->method('getDebugBacktrace')
      ->willReturn($stack);

    $result = $connection->findCallerFromDebugBacktrace();
    $this->assertEquals($expected_entry, $result);
  }

  /**
   * Provides data for testFindCallerFromDebugBacktraceWithMockedBacktrace.
   *
   * @return array[]
   *   A associative array of simple arrays, each having the following elements:
   *   - the contrib driver PHP namespace
   *   - a test debug_backtrace stack
   *   - the stack entry expected to be returned.
   *
   * @see ::testFindCallerFromDebugBacktraceWithMockedBacktrace()
   */
  public function providerMockedBacktrace(): array {
    $stack = [
      [
        'file' => '/var/www/core/lib/Drupal/Core/Database/Log.php',
        'line' => 125,
        'function' => 'findCaller',
        'class' => 'Drupal\\Core\\Database\\Log',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/libraries/drudbal/lib/Statement.php',
        'line' => 264,
        'function' => 'log',
        'class' => 'Drupal\\Core\\Database\\Log',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/libraries/drudbal/lib/Connection.php',
        'line' => 213,
        'function' => 'execute',
        'class' => 'Drupal\\Driver\\Database\\dbal\\Statement',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/core/tests/Drupal/KernelTests/Core/Database/LoggingTest.php',
        'line' => 23,
        'function' => 'query',
        'class' => 'Drupal\\Driver\\Database\\dbal\\Connection',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 1154,
        'function' => 'testEnableLogging',
        'class' => 'Drupal\\KernelTests\\Core\\Database\\LoggingTest',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 842,
        'function' => 'runTest',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestResult.php',
        'line' => 693,
        'function' => 'runBare',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => '/var/www/vendor/phpunit/phpunit/src/Framework/TestCase.php',
        'line' => 796,
        'function' => 'run',
        'class' => 'PHPUnit\\Framework\\TestResult',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => 'Standard input code',
        'line' => 57,
        'function' => 'run',
        'class' => 'PHPUnit\\Framework\\TestCase',
        'object' => 'test',
        'type' => '->',
        'args' => [
          0 => 'test',
        ],
      ],
      [
        'file' => 'Standard input code',
        'line' => 111,
        'function' => '__phpunit_run_isolated_test',
        'args' => [
          0 => 'test',
        ],
      ],
    ];

    return [
      // Test that if the driver namespace is in the stack trace, the first
      // non-database entry is returned.
      'contrib driver namespace' => [
        'Drupal\\Driver\\Database\\dbal',
        $stack,
        [
          'class' => 'Drupal\\KernelTests\\Core\\Database\\LoggingTest',
          'function' => 'testEnableLogging',
          'file' => '/var/www/core/tests/Drupal/KernelTests/Core/Database/LoggingTest.php',
          'line' => 23,
          'type' => '->',
          'args' => [
            0 => 'test',
          ],
        ],
      ],
      // Extreme case, should not happen at normal runtime - if the driver
      // namespace is not in the stack trace, the first entry to a method
      // in core database namespace is returned.
      'missing driver namespace' => [
        'Drupal\\Driver\\Database\\fake',
        $stack,
        [
          'class' => 'Drupal\\Driver\\Database\\dbal\\Statement',
          'function' => 'execute',
          'file' => '/var/www/libraries/drudbal/lib/Statement.php',
          'line' => 264,
          'type' => '->',
          'args' => [
            0 => 'test',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests deprecation of the StatementWrapper class.
   *
   * @group legacy
   */
  public function testStatementWrapperDeprecation() {
    $this->expectDeprecation('\\Drupal\\Core\\Database\\StatementWrapper is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \\Drupal\\Core\\Database\\StatementWrapperIterator instead. See https://www.drupal.org/node/3265938');
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, []);
    $this->expectError();
    $connection->prepareStatement('boing', []);
  }

  /**
   * Tests deprecation of the StatementPrefetch class.
   *
   * @group legacy
   */
  public function testStatementPrefetchDeprecation() {
    $this->expectDeprecation('\\Drupal\\Core\\Database\\StatementPrefetch is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Database\StatementPrefetchIterator instead. See https://www.drupal.org/node/3265938');
    $mockPdo = $this->createMock(StubPDO::class);
    $mockConnection = new StubConnection($mockPdo, []);
    $statement = new StatementPrefetch($mockPdo, $mockConnection, '');
    $this->assertInstanceOf(StatementPrefetch::class, $statement);
  }

}
