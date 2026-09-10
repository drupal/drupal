<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Kernel\sqlite;

use Drupal\Core\Database\SchemaDefinition\Column;
use Drupal\Core\Database\SchemaDefinition\ColumnType;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnExpression;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnStorage;
use Drupal\Core\Database\SchemaDefinition\Table;
use Drupal\Core\Database\SchemaException;
use Drupal\KernelTests\Core\Database\DriverSpecificSchemaTestBase;
use Drupal\sqlite\Driver\Database\sqlite\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore xinfo

/**
 * Tests schema API for the SQLite driver.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
#[CoversClass(Schema::class)]
class SchemaTest extends DriverSpecificSchemaTestBase {

  /**
   * {@inheritdoc}
   */
  public function checkSchemaComment(string|false $description, string $table, ?string $column = NULL): void {
    // The sqlite driver schema does not support fetching table/column
    // comments.
  }

  /**
   * {@inheritdoc}
   */
  public function checkGeneratedColumnStorage(GeneratedColumnStorage $storage, string $table, string $column): void {
    $getPrefixInfo = new \ReflectionMethod(get_class($this->schema), 'getPrefixInfo');
    $info = $getPrefixInfo->invoke($this->schema, $table);
    // table_info omits generated columns, table_xinfo reports them with a
    // hidden flag of 2 for VIRTUAL and 3 for STORED.
    $result = $this->connection->query('PRAGMA [' . $info['schema'] . '].table_xinfo([' . $info['table'] . '])');
    $expected = match ($storage) {
      GeneratedColumnStorage::Virtual => 2,
      GeneratedColumnStorage::Stored => 3,
    };
    foreach ($result as $row) {
      if ($row->name === $column) {
        $this->assertSame($expected, (int) $row->hidden);
        return;
      }
    }
    $this->fail("Column '$column' was not found in table '$table'.");
  }

  /**
   * {@inheritdoc}
   */
  protected function tryInsertExpectsIntegrityConstraintViolationException(string $tableName): void {
    // Sqlite does not throw an IntegrityConstraintViolationException here.
  }

  /**
   * {@inheritdoc}
   */
  public function testTableWithSpecificDataType(): void {
    $table_specification = [
      'description' => 'Schema table description.',
      'fields' => [
        'timestamp'  => [
          'sqlite_type' => 'datetime',
          'not null' => FALSE,
          'default' => NULL,
        ],
      ],
    ];
    $this->schema->createTable('test_timestamp', $table_specification);
    $this->assertTrue($this->schema->tableExists('test_timestamp'));
  }

  /**
   * Tests introspect index schema.
   *
   * @legacy-covers \Drupal\sqlite\Driver\Database\sqlite\Schema::introspectIndexSchema
   */
  public function testIntrospectIndexSchema(): void {
    $table_specification = [
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'test_field_1'  => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'test_field_2'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_3'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_4'  => [
          'type' => 'int',
          'default' => 0,
        ],
        'test_field_5'  => [
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'primary key' => ['id', 'test_field_1'],
      'unique keys' => [
        'test_field_2' => ['test_field_2'],
        'test_field_3_test_field_4' => ['test_field_3', 'test_field_4'],
      ],
      'indexes' => [
        'test_field_4' => ['test_field_4'],
        'test_field_4_test_field_5' => ['test_field_4', 'test_field_5'],
      ],
    ];

    $table_name = strtolower($this->getRandomGenerator()->name());
    $this->schema->createTable($table_name, $table_specification);

    unset($table_specification['fields']);

    $introspect_index_schema = new \ReflectionMethod(get_class($this->schema), 'introspectIndexSchema');
    $index_schema = $introspect_index_schema->invoke($this->schema, $table_name);

    $this->assertEquals($table_specification, $index_schema);
  }

  /**
   * Tests introspecting a table whose generated column cannot be recovered.
   */
  public function testGeneratedColumnDefinitionRecoveryFailure(): void {
    $tableName = 'test_generated_column_recovery';
    $this->schema->createTable($tableName, (new Table(
      name: $tableName,
      columns: [
        Column::int(
          name: 'age',
        ),
        Column::generated(
          name: 'double_age',
          type: ColumnType::Int,
          storage: GeneratedColumnStorage::Virtual,
          expression: new GeneratedColumnExpression('[age] * 2'),
        ),
      ],
    ))->toArray());

    // A table rebuilt from a description missing a generated column would drop
    // that column, so introspection must fail instead. Every table the schema
    // API creates can be recovered, so the failure has to be simulated by
    // overriding the recovery to find nothing.
    $schema = new class ($this->connection) extends Schema {

      /**
       * {@inheritdoc}
       */
      protected function getColumnDefinitions(string $schema, string $table): array {
        return [];
      }

    };
    $this->expectException(SchemaException::class);
    $this->expectExceptionMessage("Unable to recover the definition of generated column double_age");
    // Schema::fieldExists() introspects the whole table, so it trips on
    // 'double_age' no matter which column it is asked about.
    $schema->fieldExists($tableName, 'age');
  }

}
