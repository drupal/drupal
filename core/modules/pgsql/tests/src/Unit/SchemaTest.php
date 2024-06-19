<?php

declare(strict_types=1);

namespace Drupal\Tests\pgsql\Unit;

use Drupal\pgsql\Driver\Database\pgsql\Schema;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\pgsql\Driver\Database\pgsql\Schema
 * @group Database
 */
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
   * @covers ::constraintExists
   * @dataProvider providerComputedConstraintName
   */
  public function testComputedConstraintName($table_name, $name, $expected): void {
    $max_identifier_length = 63;

    $connection = $this->prophesize('\Drupal\pgsql\Driver\Database\pgsql\Connection');
    $connection->getConnectionOptions()->willReturn([]);
    $connection->getPrefix()->willReturn('');

    $statement = $this->prophesize('\Drupal\Core\Database\StatementInterface');
    $statement->fetchField()->willReturn($max_identifier_length);
    $connection->query('SHOW max_identifier_length')->willReturn($statement->reveal());

    $connection->query(Argument::containingString($expected))
      ->willReturn($this->prophesize('\Drupal\Core\Database\StatementInterface')->reveal())
      ->shouldBeCalled();

    $schema = new Schema($connection->reveal());
    $schema->constraintExists($table_name, $name);
  }

  /**
   * Data provider for ::testComputedConstraintName().
   */
  public static function providerComputedConstraintName() {
    return [
      ['user_field_data', 'pkey', 'user_field_data____pkey'],
      ['user_field_data', 'name__key', 'user_field_data__name__key'],
      ['user_field_data', 'a_very_very_very_very_super_long_field_name__key', 'drupal_WW_a8TlbZ3UQi20UTtRlJFaIeSa6FEtQS5h4NRA3UeU_key'],
    ];
  }

}
