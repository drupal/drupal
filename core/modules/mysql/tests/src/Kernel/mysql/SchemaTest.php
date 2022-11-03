<?php

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\KernelTests\Core\Database\DriverSpecificSchemaTestBase;

/**
 * Tests schema API for the MySQL driver.
 *
 * @group Database
 */
class SchemaTest extends DriverSpecificSchemaTestBase {

  /**
   * {@inheritdoc}
   */
  public function checkSchemaComment(string $description, string $table, string $column = NULL): void {
    $comment = $this->schema->getComment($table, $column);
    $max_length = $column ? 255 : 60;
    $description = Unicode::truncate($description, $max_length, TRUE, TRUE);
    $this->assertSame($description, $comment, 'The comment matches the schema description.');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCollation(): void {
    // Make sure that varchar fields have the correct collations.
    $columns = $this->connection->query('SHOW FULL COLUMNS FROM {test_table}');
    foreach ($columns as $column) {
      if ($column->Field == 'test_field_string') {
        $string_check = $column->Collation;
      }
      if ($column->Field == 'test_field_string_ascii') {
        $string_ascii_check = $column->Collation;
      }
    }
    $this->assertMatchesRegularExpression('#^(utf8mb4_general_ci|utf8mb4_0900_ai_ci)$#', $string_check, 'test_field_string should have a utf8mb4_general_ci or a utf8mb4_0900_ai_ci collation, but it has not.');
    $this->assertSame('ascii_general_ci', $string_ascii_check, 'test_field_string_ascii should have a ascii_general_ci collation, but it has not.');
  }

  /**
   * {@inheritdoc}
   */
  public function testTableWithSpecificDataType(): void {
    $table_specification = [
      'description' => 'Schema table description.',
      'fields' => [
        'timestamp'  => [
          'mysql_type' => 'timestamp',
          'not null' => FALSE,
          'default' => NULL,
        ],
      ],
    ];
    $this->schema->createTable('test_timestamp', $table_specification);
    $this->assertTrue($this->schema->tableExists('test_timestamp'));
  }

  /**
   * Tests that indexes on string fields are limited to 191 characters on MySQL.
   *
   * @see \Drupal\mysql\Driver\Database\mysql\Schema::getNormalizedIndexes()
   */
  public function testIndexLength(): void {
    $table_specification = [
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
        'test_field_text'  => [
          'type' => 'text',
          'not null' => TRUE,
        ],
        'test_field_string_long'  => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'test_field_string_ascii_long'  => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'test_field_string_short'  => [
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ],
      ],
      'indexes' => [
        'test_regular' => [
          'test_field_text',
          'test_field_string_long',
          'test_field_string_ascii_long',
          'test_field_string_short',
        ],
        'test_length' => [
          ['test_field_text', 128],
          ['test_field_string_long', 128],
          ['test_field_string_ascii_long', 128],
          ['test_field_string_short', 128],
        ],
        'test_mixed' => [
          ['test_field_text', 200],
          'test_field_string_long',
          ['test_field_string_ascii_long', 200],
          'test_field_string_short',
        ],
      ],
    ];
    $this->schema->createTable('test_table_index_length', $table_specification);

    // Ensure expected exception thrown when adding index with missing info.
    $expected_exception_message = "MySQL needs the 'test_field_text' field specification in order to normalize the 'test_regular' index";
    $missing_field_spec = $table_specification;
    unset($missing_field_spec['fields']['test_field_text']);
    try {
      $this->schema->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $missing_field_spec);
      $this->fail('SchemaException not thrown when adding index with missing information.');
    }
    catch (SchemaException $e) {
      $this->assertEquals($expected_exception_message, $e->getMessage());
    }

    // Add a separate index.
    $this->schema->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $table_specification);
    $table_specification_with_new_index = $table_specification;
    $table_specification_with_new_index['indexes']['test_separate'] = [['test_field_text', 200]];

    // Ensure that the exceptions of addIndex are thrown as expected.
    try {
      $this->schema->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $table_specification);
      $this->fail('\Drupal\Core\Database\SchemaObjectExistsException exception missed.');
    }
    catch (SchemaObjectExistsException $e) {
      // Expected exception; just continue testing.
    }

    try {
      $this->schema->addIndex('test_table_non_existing', 'test_separate', [['test_field_text', 200]], $table_specification);
      $this->fail('\Drupal\Core\Database\SchemaObjectDoesNotExistException exception missed.');
    }
    catch (SchemaObjectDoesNotExistException $e) {
      // Expected exception; just continue testing.
    }

    // Get index information.
    $results = $this->connection->query('SHOW INDEX FROM {test_table_index_length}');
    $expected_lengths = [
      'test_regular' => [
        'test_field_text' => 191,
        'test_field_string_long' => 191,
        'test_field_string_ascii_long' => NULL,
        'test_field_string_short' => NULL,
      ],
      'test_length' => [
        'test_field_text' => 128,
        'test_field_string_long' => 128,
        'test_field_string_ascii_long' => 128,
        'test_field_string_short' => NULL,
      ],
      'test_mixed' => [
        'test_field_text' => 191,
        'test_field_string_long' => 191,
        'test_field_string_ascii_long' => 200,
        'test_field_string_short' => NULL,
      ],
      'test_separate' => [
        'test_field_text' => 191,
      ],
    ];

    // Count the number of columns defined in the indexes.
    $column_count = 0;
    foreach ($table_specification_with_new_index['indexes'] as $index) {
      foreach ($index as $field) {
        $column_count++;
      }
    }
    $test_count = 0;
    foreach ($results as $result) {
      $this->assertEquals($expected_lengths[$result->Key_name][$result->Column_name], $result->Sub_part, 'Index length matches expected value.');
      $test_count++;
    }
    $this->assertEquals($column_count, $test_count, 'Number of tests matches expected value.');
  }

  /**
   * @covers \Drupal\mysql\Driver\Database\mysql\Schema::introspectIndexSchema
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
    $introspect_index_schema->setAccessible(TRUE);
    $index_schema = $introspect_index_schema->invoke($this->schema, $table_name);

    $this->assertEquals($table_specification, $index_schema);
  }

}
