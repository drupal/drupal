<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\SchemaDefinition\Column;
use Drupal\Core\Database\SchemaDefinition\ColumnType;
use Drupal\Core\Database\SchemaDefinition\FloatValue;
use Drupal\Core\Database\SchemaDefinition\ForeignKey;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnExpression;
use Drupal\Core\Database\SchemaDefinition\GeneratedColumnStorage;
use Drupal\Core\Database\SchemaDefinition\IntValue;
use Drupal\Core\Database\SchemaDefinition\NullValue;
use Drupal\Core\Database\SchemaDefinition\PrimaryKey;
use Drupal\Core\Database\SchemaDefinition\Schema;
use Drupal\Core\Database\SchemaDefinition\SchemaDefinitionType;
use Drupal\Core\Database\SchemaDefinition\StringValue;
use Drupal\Core\Database\SchemaDefinition\Table;
use Drupal\Core\Database\SchemaDefinition\UniqueKey;
use Drupal\Core\Database\Exception\SchemaDefinitionException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests SchemaDefinition Column object validations.
 */
#[Group('Database')]
#[CoversClass(Schema::class)]
#[CoversClass(Table::class)]
#[CoversClass(Column::class)]
class SchemaDefinitionTest extends UnitTestCase {

  /**
   * Tests Schema.
   */
  public function testSchemaMethods(): void {
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

    // Check ::tableNames().
    $this->assertSame(['foo', 'bar'], $schemaDefinition->tableNames());

    // Check ::getTableDefinition().
    $tableDefinition = $schemaDefinition->getTableDefinition('foo');
    $this->assertInstanceOf(Table::class, $tableDefinition);
    $this->assertSame('foo', $tableDefinition->name);
    $this->assertSame('Foo table for the database unit tests.', $tableDefinition->description);
  }

  /**
   * Tests passing a non Table array member to Schema::$tables.
   */
  public function testInvalidTableInSchema(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('All members of the \'tables\' argument of a Schema must be Table objects');
    $table = new Schema(
      type: SchemaDefinitionType::Test,
      name: 'test',
      tables: [
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
      ],
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a non Column array member to Table::$columns.
   */
  public function testInvalidColumnInTable(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('All members of the \'columns\' argument of the \'test\' Table must be Column objects');
    $table = new Table(
      name: 'test',
      columns: [
        'name' => [
          'description' => "A person's name",
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'binary' => TRUE,
        ],
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a non Column array member to Table::$columns.
   */
  public function testInvalidForeignKeyForMismatchingColumnCount(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Mismatching count of columns for the \'user_id\' foreign key');
    $table = new Table(
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
      ],
      primaryKey: new PrimaryKey(['id']),
      foreignKeys: [
        new ForeignKey(
          name: 'user_id',
          foreignTable: 'test_users',
          columns: ['id', 'name'],
          foreignColumns: ['id'],
        ),
      ],
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests Serial column.
   */
  public function testInvalidSerialForDefault(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a int default for column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Serial,
          default: new IntValue(1),
        ),
        Column::varcharAscii(
          name: 'foo',
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests Serial column.
   */
  public function testInvalidSerialForNotNull(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'notNull\' for serial column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Serial,
          notNull: TRUE,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests Serial column.
   */
  public function testInvalidSerialForUnsigned(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'unsigned\' for serial column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Serial,
          unsigned: TRUE,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests Serial column.
   */
  public function testInvalidSerialForGeneratedExpression(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'generatedExpression\' for serial column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::generated(
          name: 'id',
          type: ColumnType::Serial,
          expression: new GeneratedColumnExpression('1 + 1'),
          storage: GeneratedColumnStorage::Virtual,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests that a generated column cannot be part of the primary key.
   */
  public function testInvalidPrimaryKeyForGeneratedColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage("The primary key of the 'test' Table cannot include generated columns");
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'age',
          type: ColumnType::Int,
        ),
        Column::generated(
          name: 'double_age',
          type: ColumnType::Int,
          expression: new GeneratedColumnExpression('[age] * 2'),
          storage: GeneratedColumnStorage::Stored,
        ),
      ],
      primaryKey: new PrimaryKey(['double_age']),
    );
  }

  /**
   * Tests passing a null default when notNull: TRUE is specified.
   */
  public function testNullDefaultForNotNullArgument(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a null default for column \'id\': notNull is true');
    new Table(
      name: 'test',
      columns: [
        Column::int(
          name: 'id',
          notNull: TRUE,
          default: new NullValue(),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests passing a float default to an int column.
   */
  public function testFloatDefaultForIntColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a float default for column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Int,
          default: new FloatValue(10),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests passing a int default to an int column.
   */
  public function testIntDefaultForIntColumn(): void {
    $table = new Table(
      name: 'test',
      columns: [
        Column::int(
          name: 'id',
          default: new IntValue(10),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a null default to an int column.
   */
  public function testNullDefaultForIntColumn(): void {
    $table = new Table(
      name: 'test',
      columns: [
        Column::int(
          name: 'id',
          default: new NullValue(),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a string default to an int column.
   */
  public function testStringDefaultForIntColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a string default for column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Int,
          default: new StringValue('foo'),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests passing a float default to a text column.
   */
  public function testFloatDefaultForTextColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a float default for column \'id\'');
    new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Text,
          default: new FloatValue(10),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
  }

  /**
   * Tests passing a int default to a text column.
   */
  public function testIntDefaultForTextColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessageIs('Cannot set a int default for column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Text,
          default: new IntValue(10),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a null default to a text column.
   */
  public function testNullDefaultForTextColumn(): void {
    $table = new Table(
      name: 'test',
      columns: [
        Column::text(
          name: 'id',
          default: new NullValue(),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a string default to a text column.
   */
  public function testStringDefaultForTextColumn(): void {
    $table = new Table(
      name: 'test',
      columns: [
        Column::text(
          name: 'id',
          default: new StringValue('foo'),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing unsigned to a non-numeric column.
   */
  public function testInvalidColumnTypeForUnsigned(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'unsigned\' for text column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Text,
          unsigned: TRUE,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing length to a non-textual column.
   */
  public function testInvalidColumnTypeForLength(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'length\' for int column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Int,
          length: 20,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing binary to a non-textual column.
   */
  public function testInvalidColumnTypeForBinary(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'binary\' for int column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Int,
          binary: TRUE,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a scale to a non-numeric column.
   */
  public function testInvalidColumnTypeForScale(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'scale\' for text column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Text,
          scale: 4,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a precision to a non-numeric column.
   */
  public function testInvalidColumnTypeForPrecision(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'precision\' for text column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Text,
          precision: 2,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing a serialize to a non-text/blob column.
   */
  public function testInvalidColumnTypeForSerialize(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Cannot set \'serialize\' for int column \'id\'');
    $table = new Table(
      name: 'test',
      columns: [
        Column::create(
          name: 'id',
          type: ColumnType::Int,
          serialize: TRUE,
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing limited length columns to primary key.
   */
  public function testInvalidPrimaryKeyForPartialLengthColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Limited length columns not allowed in this context');
    $table = new Table(
      name: 'test',
      columns: [
        Column::text(
          name: 'id',
          length: 40,
          default: new StringValue('foo'),
        ),
      ],
      primaryKey: new PrimaryKey([['id', 10]]),
    );
    $this->assertInstanceOf(Table::class, $table);
  }

  /**
   * Tests passing limited length columns to an unique key.
   */
  public function testInvalidUniqueKeyForPartialLengthColumn(): void {
    $this->expectException(SchemaDefinitionException::class);
    $this->expectExceptionMessage('Limited length columns not allowed in this context');
    $table = new Table(
      name: 'test',
      columns: [
        Column::text(
          name: 'id',
          length: 40,
          default: new StringValue('foo'),
        ),
      ],
      primaryKey: new PrimaryKey(['id']),
      uniqueKeys: [
        new UniqueKey(
          name: 'invalid',
          columns: [['id', 10]],
        ),
      ],
    );
    $this->assertInstanceOf(Table::class, $table);
  }

}
