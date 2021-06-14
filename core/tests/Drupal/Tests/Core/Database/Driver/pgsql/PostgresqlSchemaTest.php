<?php

namespace Drupal\Tests\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Driver\pgsql\Schema;
use Drupal\Tests\UnitTestCase;

// cSpell:ignore conname

/**
 * @coversDefaultClass \Drupal\Core\Database\Driver\pgsql\Schema
 * @group Database
 */
class PostgresqlSchemaTest extends UnitTestCase {

  /**
   * The PostgreSql DB connection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Database\Driver\pgsql\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Driver\pgsql\Connection')
      ->disableOriginalConstructor()
      ->getMock();
  }

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
  public function testComputedConstraintName($table_name, $name, $expected) {
    $max_identifier_length = 63;
    $schema = new Schema($this->connection);

    $statement = $this->createMock('\Drupal\Core\Database\StatementInterface');
    $statement->expects($this->any())
      ->method('fetchField')
      ->willReturn($max_identifier_length);

    $this->connection->expects($this->any())
      ->method('query')
      ->willReturn($statement);

    $this->connection->expects($this->at(2))
      ->method('query')
      ->with("SELECT 1 FROM pg_constraint WHERE conname = '$expected'")
      ->willReturn($this->createMock('\Drupal\Core\Database\StatementInterface'));

    $schema->constraintExists($table_name, $name);
  }

  /**
   * Data provider for ::testComputedConstraintName().
   */
  public function providerComputedConstraintName() {
    return [
      ['user_field_data', 'pkey', 'user_field_data____pkey'],
      ['user_field_data', 'name__key', 'user_field_data__name__key'],
      ['user_field_data', 'a_veeeery_veery_very_super_long_field_name__key', 'drupal_BGGYAXgbqlAF1rMOyFTdZGj9zIMXZtSvEjMAKZ9wGIk_key'],
    ];
  }

}
