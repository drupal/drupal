<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\SchemaDefinition\Column;
use Drupal\Core\Database\SchemaDefinition\ColumnSize;
use Drupal\Core\Database\SchemaDefinition\ColumnType;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnExpression;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnStorage;
use Drupal\Core\Database\SchemaDefinition\ForeignKey;
use Drupal\Core\Database\SchemaDefinition\Index;
use Drupal\Core\Database\SchemaDefinition\IntValue;
use Drupal\Core\Database\SchemaDefinition\KeyColumn;
use Drupal\Core\Database\SchemaDefinition\NullValue;
use Drupal\Core\Database\SchemaDefinition\PrimaryKey;
use Drupal\Core\Database\SchemaDefinition\StringValue;
use Drupal\Core\Database\SchemaDefinition\Schema;
use Drupal\Core\Database\SchemaDefinition\SchemaDefinitionType;
use Drupal\Core\Database\SchemaDefinition\Table;
use Drupal\Core\Database\SchemaDefinition\UniqueKey;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests conversion of SchemaDefinition objects to legacy array-based structure.
 */
#[Group('Database')]
#[CoversClass(Table::class)]
#[CoversClass(Schema::class)]
class SchemaDefinitionConversionTest extends UnitTestCase {

  /**
   * Tests Table::toArray().
   */
  public function testConvertTableDefinition(): void {
    $arraySpecification = [
      'description' => 'Basic test table for the database unit tests.',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'name' => [
          'description' => "A person's name",
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
        ],
        'age' => [
          'description' => "The person's age",
          'type' => 'int',
          'size' => 'small',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'double_age' => [
          'description' => "The person's age X 2",
          'type' => 'int',
          'generated' => Column::generated(
            name: 'double_age',
            type: ColumnType::Int,
            description: "The person's age X 2",
            storage: GeneratedColumnStorage::Virtual,
            expression: new GeneratedColumnExpression('[age] * 2'),
            dbSpecificExtra: ['pgsql' => ['pgsql_type' => 'bigint']],
          ),
          'pgsql_type' => 'bigint',
        ],
        'job' => [
          'description' => "The person's job",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => 'Undefined',
        ],
        'db_timestamp' => [
          'description' => "The database timestamp",
          'mysql_type' => 'timestamp',
          'pgsql_type' => 'timestamp',
          'sqlite_type' => 'datetime',
          'not null' => FALSE,
          'default' => NULL,
        ],
      ],
      'primary key' => ['id'],
      'unique keys' => [
        'name' => ['name'],
      ],
      'indexes' => [
        'ages' => ['age'],
        'age_job_prefix' => ['age', ['job', 50]],
        'age_name_prefix' => ['age', ['name', 20]],
        'foo' => ['job'],
      ],
      'foreign keys' => [
        'user_id' => [
          'table' => 'test_users',
          'columns' => ['id' => 'id'],
        ],
      ],
      'mysql_engine' => 'InnoDB',
      'mysql_character_set' => 'utf8mb4',
    ];

    $schemaDefinition = new Table(
      name: 'test',
      description: 'Basic test table for the database unit tests.',
      columns: [
        Column::serial(
          name: 'id',
        ),
        Column::varcharAscii(
          name: 'name',
          description: "A person's name",
          length: 255,
          notNull: TRUE,
          default: new StringValue(''),
          binary: TRUE,
        ),
        Column::int(
          name: 'age',
          description: "The person's age",
          size: ColumnSize::Small,
          unsigned: TRUE,
          notNull: TRUE,
          default: new IntValue(0),
        ),
        Column::generated(
          name: 'double_age',
          type: ColumnType::Int,
          description: "The person's age X 2",
          storage: GeneratedColumnStorage::Virtual,
          expression: new GeneratedColumnExpression('[age] * 2'),
          dbSpecificExtra: ['pgsql' => ['pgsql_type' => 'bigint']],
        ),
        Column::varchar(
          name: 'job',
          description: "The person's job",
          length: 255,
          notNull: TRUE,
          default: new StringValue('Undefined'),
        ),
        Column::create(
          name: 'db_timestamp',
          description: "The database timestamp",
          dbSpecificExtra: [
            'mysql' => [
              'mysql_type' => 'timestamp',
            ],
            'pgsql' => [
              'pgsql_type' => 'timestamp',
            ],
            'sqlite' => [
              'sqlite_type' => 'datetime',
            ],
          ],
          notNull: FALSE,
          default: new NullValue(),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
      uniqueKeys: [
        new UniqueKey(
          name: 'name',
          columns: ['name'],
        ),
      ],
      indexes: [
        new Index(
          name: 'ages',
          columns: ['age'],
        ),
        new Index(
          name: 'age_job_prefix',
          columns: ['age', ['job', 50]],
        ),
        new Index(
          name: 'age_name_prefix',
          columns: ['age', new KeyColumn(name: 'name', length: 20)],
        ),
        new Index(
          name: 'foo',
          columns: ['job'],
        ),
      ],
      foreignKeys: [
        new ForeignKey(
          name: 'user_id',
          foreignTable: 'test_users',
          columns: ['id'],
          foreignColumns: ['id'],
        ),
      ],
      dbSpecificExtra: [
        'mysql' => [
          'mysql_engine' => 'InnoDB',
          'mysql_character_set' => 'utf8mb4',
        ],
      ],
    );

    $this->assertEquals($arraySpecification, $schemaDefinition->toArray());
  }

  /**
   * Tests Schema::toArray().
   */
  public function testConvertSchemaDefinition(): void {
    $arraySpecification = [
      'foo' => [
        'description' => 'Foo table for the database unit tests.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
      ],
      'bar' => [
        'description' => 'Bar table for the database unit tests.',
        'fields' => [
          'id' => [
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
      ],
    ];

    $schemaDefinition = new Schema(
      type: SchemaDefinitionType::Test,
      name: 'test_foo_bar',
      tables: [
        new Table(
          name: 'foo',
          description: 'Foo table for the database unit tests.',
          columns: [
            Column::serial(
              name: 'id',
            ),
          ],
          primaryKey: new PrimaryKey(['id']),
        ),
        new Table(
          name: 'bar',
          description: 'Bar table for the database unit tests.',
          columns: [
            Column::serial(
              name: 'id',
            ),
          ],
          primaryKey: new PrimaryKey(['id']),
        ),
      ],
    );

    $this->assertEquals($arraySpecification, $schemaDefinition->toArray());
  }

}
