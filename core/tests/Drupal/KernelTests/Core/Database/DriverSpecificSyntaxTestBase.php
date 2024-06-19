<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests driver specific SQL syntax interpretation.
 */
abstract class DriverSpecificSyntaxTestBase extends DriverSpecificDatabaseTestBase {

  /**
   * Tests allowing square brackets in queries.
   *
   * This method should be overridden if the SQL syntax of the test queries is
   * not compatible with a non-core database driver. For example, the unquoted
   * 'name' identifier in Oracle is a reserved keyword that would let the test
   * query fail.
   *
   * @see \Drupal\Core\Database\Connection::prepareQuery()
   */
  public function testAllowSquareBrackets(): void {
    $this->connection->insert('test')
      ->fields(['name'])
      ->values([
        'name' => '[square]',
      ])
      ->execute();

    // Note that this is a very bad example query because arguments should be
    // passed in via the $args parameter.
    $result = $this->connection->query("select name from {test} where name = '[square]'", [], ['allow_square_brackets' => TRUE]);
    $this->assertSame('[square]', $result->fetchField());

    // Test that allow_square_brackets has no effect on arguments.
    $result = $this->connection->query("select [name] from {test} where [name] = :value", [':value' => '[square]']);
    $this->assertSame('[square]', $result->fetchField());
    $result = $this->connection->query("select name from {test} where name = :value", [':value' => '[square]'], ['allow_square_brackets' => TRUE]);
    $this->assertSame('[square]', $result->fetchField());

    // Test square brackets using the query builder.
    $result = $this->connection->select('test')->fields('test', ['name'])->condition('name', '[square]')->execute();
    $this->assertSame('[square]', $result->fetchField());
  }

}
