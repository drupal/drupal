<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cSpell:ignore ilike

/**
 * Tests casting of non-text fields for LIKE and regex operators.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class ConditionCastTest extends DriverSpecificDatabaseTestBase {

  /**
   * Tests condition operators on an integer column.
   *
   * PostgreSQL has no LIKE or regex operators for integers, so these queries
   * only work when the field is cast to text. For query builder conditions
   * the cast is added when the condition is compiled.
   *
   * @see \Drupal\pgsql\Driver\Database\pgsql\Connection::$postgresqlConditionOperatorMap
   */
  public function testConditionOperatorCastOnIntegerField(): void {
    // The ages in the sample data are 25, 27, 28 and 26.
    $cases = [
      ['LIKE', '2%', 4],
      ['LIKE', '%5', 1],
      ['NOT LIKE', '%5', 3],
      ['LIKE BINARY', '%7', 1],
      ['ILIKE', '%6', 1],
      ['NOT ILIKE', '%6', 3],
      ['REGEXP', '^2[57]$', 2],
      ['NOT REGEXP', '^2[57]$', 2],
      ['~*', '8$', 1],
      ['!~*', '8$', 3],
    ];
    foreach ($cases as [$operator, $value, $expected_count]) {
      $query = $this->connection->select('test');
      $query->addField('test', 'name');
      $query->condition('age', $value, $operator);
      $this->assertStringContainsString('::text', (string) $query, "The compiled condition for operator '$operator' contains a type-cast.");
      $this->assertCount($expected_count, $query->execute()->fetchAll(), "Operator '$operator' with value '$value' returns $expected_count rows.");
    }
  }

  /**
   * Tests LIKE and regex operators in raw SQL on an integer column.
   *
   * SQL passed as a string does not go through condition compilation, so
   * prepareStatement() adds the '::text' cast to these queries. Relying on
   * this rewrite is deprecated.
   *
   * @see \Drupal\pgsql\Driver\Database\pgsql\Connection::prepareStatement()
   */
  #[IgnoreDeprecations]
  public function testRawSqlOperatorCastOnIntegerField(): void {
    $this->expectUserDeprecationMessage('Relying on the PostgreSQL database driver to add a ::text type-cast to fields used with LIKE and regular expression operators in SQL passed as a string is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Add the ::text cast to the field explicitly or use the query builder. See https://www.drupal.org/node/3615418');

    $statement = $this->connection->prepareStatement('SELECT COUNT(*) FROM {test} WHERE [age] LIKE :pattern', []);
    $this->assertStringContainsString('::text', $statement->getQueryString());

    $count = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [age] LIKE :pattern', [':pattern' => '2%'])->fetchField();
    $this->assertSame(4, (int) $count);

    $count = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [age] ~* :pattern', [':pattern' => '8$'])->fetchField();
    $this->assertSame(1, (int) $count);

    // A raw condition snippet inside a query builder query.
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->where('[age] LIKE :pattern', [':pattern' => '%6']);
    $this->assertCount(1, $query->execute()->fetchAll());
  }

  /**
   * Tests that raw SQL with an explicit cast does not trigger a deprecation.
   */
  public function testRawSqlWithExplicitCast(): void {
    $count = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [age]::text LIKE :pattern', [':pattern' => '2%'])->fetchField();
    $this->assertSame(4, (int) $count);
  }

}
