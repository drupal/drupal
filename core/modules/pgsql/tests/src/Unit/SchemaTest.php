<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Unit;

use Drupal\Core\Database\StatementInterface;
use Drupal\pgsql\Driver\Database\pgsql\Connection;
use Drupal\pgsql\Driver\Database\pgsql\Schema;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\pgsql\Driver\Database\pgsql\Schema.
 */
#[CoversClass(Schema::class)]
#[Group('Database')]
class SchemaTest extends UnitTestCase {

  /**
   * Tests whether the actual constraint name is correctly computed.
   *
   * @param string $table_name
   *   The table name the constrained column belongs to.
   * @param string $name
   *   The constraint name.
   * @param string $expected
   *   The expected computed constraint name.
   *
   * @legacy-covers ::constraintExists
   */
  #[DataProvider('providerComputedConstraintName')]
  public function testComputedConstraintName(string $table_name, string $name, string $expected): void {
    $max_identifier_length = 63;

    $connection = $this->createMock(Connection::class);
    $connection->method('getConnectionOptions')->willReturn([]);
    $connection->method('getPrefix')->willReturn('');

    $statement = $this->createStub(StatementInterface::class);
    $statement->method('fetchField')->willReturn($max_identifier_length);
    $matched_statement = $this->createStub(StatementInterface::class);

    $connection->expects($this->exactly(2))
      ->method('query')
      ->willReturnCallback(function (string $query) use ($expected, $statement, $matched_statement) {
        if ($query === 'SHOW max_identifier_length') {
          return $statement;
        }
        $this->assertStringContainsString($expected, $query);
        return $matched_statement;
      });

    $schema = new Schema($connection);
    $schema->constraintExists($table_name, $name);
  }

  /**
   * Data provider for ::testComputedConstraintName().
   */
  public static function providerComputedConstraintName(): array {
    return [
      ['user_field_data', 'pkey', 'user_field_data____pkey'],
      ['user_field_data', 'name__key', 'user_field_data__name__key'],
      [
        'user_field_data',
        'a_very_very_very_very_super_long_field_name__key',
        'drupal_WW_a8TlbZ3UQi20UTtRlJFaIeSa6FEtQS5h4NRA3UeU_key',
      ],
    ];
  }

}
