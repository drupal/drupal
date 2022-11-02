<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\SchemaException;
use Drupal\Tests\Core\Database\SchemaIntrospectionTestTrait;

/**
 * Tests table creation and modification via the schema API.
 */
abstract class DriverSpecificSchemaTestBase extends DriverSpecificKernelTestBase {

  use SchemaIntrospectionTestTrait;

  /**
   * Database schema instance.
   */
  protected Schema $schema;

  /**
   * A global counter for table and field creation.
   */
  protected int $counter = 0;

  /**
   * Connection to the database.
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->schema = $this->connection->schema();
  }

  /**
   * Checks that a table or column comment matches a given description.
   *
   * @param string $description
   *   The asserted description.
   * @param string $table
   *   The table to test.
   * @param string|null $column
   *   Optional column to test.
   */
  abstract public function checkSchemaComment(string $description, string $table, string $column = NULL): void;

  /**
   * Tests inserting data into an existing table.
   *
   * @param string $table
   *   The database table to insert data into.
   *
   * @return bool
   *   TRUE if the insert succeeded, FALSE otherwise.
   */
  public function tryInsert(string $table = 'test_table'): bool {
    try {
      $this->connection
        ->insert($table)
        ->fields(['id' => mt_rand(10, 20)])
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Tries to insert a negative value into columns defined as unsigned.
   *
   * @param string $table_name
   *   The table to insert.
   * @param string $column_name
   *   The column to insert.
   *
   * @return bool
   *   TRUE if the insert succeeded, FALSE otherwise.
   */
  public function tryUnsignedInsert(string $table_name, string $column_name): bool {
    try {
      $this->connection
        ->insert($table_name)
        ->fields([$column_name => -1])
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Tries to insert a value that throws an IntegrityConstraintViolationException.
   *
   * @param string $tableName
   *   The table to insert.
   */
  protected function tryInsertExpectsIntegrityConstraintViolationException(string $tableName): void {
    try {
      $this->connection
        ->insert($tableName)
        ->fields(['test_field_string' => 'test'])
        ->execute();
      $this->fail('Expected IntegrityConstraintViolationException not thrown');
    }
    catch (IntegrityConstraintViolationException $e) {
      // Do nothing, it's the expected behavior.
    }
  }

  /**
   * Asserts that fields have the correct collation, if supported.
   */
  protected function assertCollation(): void {
    // Driver specific tests should implement this when appropriate.
  }

  /**
   * Check that the ID sequence gets renamed when the table is renamed.
   *
   * @param string $tableName
   *   The table to rename.
   */
  protected function checkSequenceRenaming(string $tableName): void {
    // Driver specific tests should implement this when appropriate.
  }

  /**
   * Tests database interactions.
   */
  public function testSchema(): void {
    // Try creating a table.
    $table_specification = [
      'description' => 'Schema table description may contain "quotes" and could be long—very long indeed.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'Schema table description may contain "quotes" and could be long—very long indeed. There could be "multiple quoted regions".',
        ],
        'test_field_string'  => [
          'type' => 'varchar',
          'length' => 20,
          'not null' => TRUE,
          'default' => "'\"funky default'\"",
          'description' => 'Schema column description for string.',
        ],
        'test_field_string_ascii'  => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'description' => 'Schema column description for ASCII string.',
        ],
      ],
    ];
    $this->schema->createTable('test_table', $table_specification);

    // Assert that the table exists.
    $this->assertTrue($this->schema->tableExists('test_table'), 'The table exists.');

    // Assert that the table comment has been set.
    $this->checkSchemaComment($table_specification['description'], 'test_table');

    // Assert that the column comment has been set.
    $this->checkSchemaComment($table_specification['fields']['test_field']['description'], 'test_table', 'test_field');

    // Make sure that fields have the correct collation, if supported.
    $this->assertCollation();

    // An insert without a value for the column 'test_table' should fail.
    $this->assertFalse($this->tryInsert(), 'Insert without a default failed.');

    // Add a default value to the column.
    $this->schema->changeField('test_table', 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE, 'default' => 0]);
    // The insert should now succeed.
    $this->assertTrue($this->tryInsert(), 'Insert with a default succeeded.');

    // Remove the default.
    $this->schema->changeField('test_table', 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE]);
    // The insert should fail again.
    $this->assertFalse($this->tryInsert(), 'Insert without a default failed.');

    // Test for fake index and test for the boolean result of indexExists().
    $index_exists = $this->schema->indexExists('test_table', 'test_field');
    $this->assertFalse($index_exists, 'Fake index does not exist');
    // Add index.
    $this->schema->addIndex('test_table', 'test_field', ['test_field'], $table_specification);
    // Test for created index and test for the boolean result of indexExists().
    $index_exists = $this->schema->indexExists('test_table', 'test_field');
    $this->assertTrue($index_exists, 'Index created.');

    // Rename the table.
    $this->assertNull($this->schema->renameTable('test_table', 'test_table2'));

    // Index should be renamed.
    $index_exists = $this->schema->indexExists('test_table2', 'test_field');
    $this->assertTrue($index_exists, 'Index was renamed.');

    // We need the default so that we can insert after the rename.
    $this->schema->changeField('test_table2', 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE, 'default' => 0]);
    $this->assertFalse($this->tryInsert(), 'Insert into the old table failed.');
    $this->assertTrue($this->tryInsert('test_table2'), 'Insert into the new table succeeded.');

    // We should have successfully inserted exactly two rows.
    $count = $this->connection->query('SELECT COUNT(*) FROM {test_table2}')->fetchField();
    $this->assertEquals(2, $count, 'Two fields were successfully inserted.');

    // Try to drop the table.
    $this->schema->dropTable('test_table2');
    $this->assertFalse($this->schema->tableExists('test_table2'), 'The dropped table does not exist.');

    // Recreate the table.
    $this->schema->createTable('test_table', $table_specification);
    $this->schema->changeField('test_table', 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE, 'default' => 0]);
    $this->schema->addField('test_table', 'test_serial', ['type' => 'int', 'not null' => TRUE, 'default' => 0, 'description' => 'Added column description.']);

    // Assert that the column comment has been set.
    $this->checkSchemaComment('Added column description.', 'test_table', 'test_serial');

    // Change the new field to a serial column.
    $this->schema->changeField('test_table', 'test_serial', 'test_serial', ['type' => 'serial', 'not null' => TRUE, 'description' => 'Changed column description.'], ['primary key' => ['test_serial']]);

    // Assert that the column comment has been set.
    $this->checkSchemaComment('Changed column description.', 'test_table', 'test_serial');

    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max1 = $this->connection->query('SELECT MAX([test_serial]) FROM {test_table}')->fetchField();
    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max2 = $this->connection->query('SELECT MAX([test_serial]) FROM {test_table}')->fetchField();
    $this->assertTrue($max2 > $max1, 'The serial is monotone.');

    $count = $this->connection->query('SELECT COUNT(*) FROM {test_table}')->fetchField();
    $this->assertEquals(2, $count, 'There were two rows.');

    // Test adding a serial field to an existing table.
    $this->schema->dropTable('test_table');
    $this->schema->createTable('test_table', $table_specification);
    $this->schema->changeField('test_table', 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE, 'default' => 0]);
    $this->schema->addField('test_table', 'test_serial', ['type' => 'serial', 'not null' => TRUE], ['primary key' => ['test_serial']]);

    // Test the primary key columns.
    $method = new \ReflectionMethod(get_class($this->schema), 'findPrimaryKeyColumns');
    $method->setAccessible(TRUE);
    $this->assertSame(['test_serial'], $method->invoke($this->schema, 'test_table'));

    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max1 = $this->connection->query('SELECT MAX([test_serial]) FROM {test_table}')->fetchField();
    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max2 = $this->connection->query('SELECT MAX([test_serial]) FROM {test_table}')->fetchField();
    $this->assertTrue($max2 > $max1, 'The serial is monotone.');

    $count = $this->connection->query('SELECT COUNT(*) FROM {test_table}')->fetchField();
    $this->assertEquals(2, $count, 'There were two rows.');

    // Test adding a new column and form a composite primary key with it.
    $this->schema->addField('test_table', 'test_composite_primary_key', ['type' => 'int', 'not null' => TRUE, 'default' => 0], ['primary key' => ['test_serial', 'test_composite_primary_key']]);

    // Test the primary key columns.
    $this->assertSame(['test_serial', 'test_composite_primary_key'], $method->invoke($this->schema, 'test_table'));

    // Test renaming of keys and constraints.
    $this->schema->dropTable('test_table');
    $table_specification = [
      'fields' => [
        'id'  => [
          'type' => 'serial',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'primary key' => ['id'],
      'unique keys' => [
        'test_field' => ['test_field'],
      ],
    ];

    // PostgreSQL has a max identifier length of 63 characters, MySQL has 64 and
    // SQLite does not have any limit. Use the lowest common value and create a
    // table name as long as possible in order to cover edge cases around
    // identifier names for the table's primary or unique key constraints.
    $table_name = strtolower($this->getRandomGenerator()->name(63 - strlen($this->getDatabasePrefix())));
    $this->schema->createTable($table_name, $table_specification);

    $this->assertIndexOnColumns($table_name, ['id'], 'primary');
    $this->assertIndexOnColumns($table_name, ['test_field'], 'unique');

    $new_table_name = strtolower($this->getRandomGenerator()->name(63 - strlen($this->getDatabasePrefix())));
    $this->assertNull($this->schema->renameTable($table_name, $new_table_name));

    // Test for renamed primary and unique keys.
    $this->assertIndexOnColumns($new_table_name, ['id'], 'primary');
    $this->assertIndexOnColumns($new_table_name, ['test_field'], 'unique');

    // Check that the ID sequence gets renamed when the table is renamed.
    $this->checkSequenceRenaming($new_table_name);
  }

  /**
   * Tests creating a table with database specific data type.
   */
  abstract public function testTableWithSpecificDataType(): void;

  /**
   * Tests creating unsigned columns and data integrity thereof.
   */
  public function testUnsignedColumns(): void {
    // First create the table with just a serial column.
    $table_name = 'unsigned_table';
    $table_spec = [
      'fields' => ['serial_column' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE]],
      'primary key' => ['serial_column'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    // Now set up columns for the other types.
    $types = ['int', 'float', 'numeric'];
    foreach ($types as $type) {
      $column_spec = ['type' => $type, 'unsigned' => TRUE];
      if ($type == 'numeric') {
        $column_spec += ['precision' => 10, 'scale' => 0];
      }
      $column_name = $type . '_column';
      $table_spec['fields'][$column_name] = $column_spec;
      $this->schema->addField($table_name, $column_name, $column_spec);
    }

    // Finally, check each column and try to insert invalid values into them.
    foreach ($table_spec['fields'] as $column_name => $column_spec) {
      $this->assertTrue($this->schema->fieldExists($table_name, $column_name), new FormattableMarkup('Unsigned @type column was created.', ['@type' => $column_spec['type']]));
      $this->assertFalse($this->tryUnsignedInsert($table_name, $column_name), new FormattableMarkup('Unsigned @type column rejected a negative value.', ['@type' => $column_spec['type']]));
    }
  }

  /**
   * Tests adding columns to an existing table with default and initial value.
   */
  public function testSchemaAddFieldDefaultInitial(): void {
    // Test varchar types.
    foreach ([1, 32, 128, 256, 512] as $length) {
      $base_field_spec = [
        'type' => 'varchar',
        'length' => $length,
      ];
      $variations = [
        ['not null' => FALSE],
        ['not null' => FALSE, 'default' => '7'],
        ['not null' => FALSE, 'default' => substr('"thing"', 0, $length)],
        ['not null' => FALSE, 'default' => substr("\"'hing", 0, $length)],
        ['not null' => TRUE, 'initial' => 'd'],
        ['not null' => FALSE, 'default' => NULL],
        ['not null' => TRUE, 'initial' => 'd', 'default' => '7'],
      ];

      foreach ($variations as $variation) {
        $field_spec = $variation + $base_field_spec;
        $this->assertFieldAdditionRemoval($field_spec);
      }
    }

    // Test int and float types.
    foreach (['int', 'float'] as $type) {
      foreach (['tiny', 'small', 'medium', 'normal', 'big'] as $size) {
        $base_field_spec = [
          'type' => $type,
          'size' => $size,
        ];
        $variations = [
          ['not null' => FALSE],
          ['not null' => FALSE, 'default' => 7],
          ['not null' => TRUE, 'initial' => 1],
          ['not null' => TRUE, 'initial' => 1, 'default' => 7],
          ['not null' => TRUE, 'initial_from_field' => 'serial_column'],
          [
            'not null' => TRUE,
            'initial_from_field' => 'test_nullable_field',
            'initial'  => 100,
          ],
        ];

        foreach ($variations as $variation) {
          $field_spec = $variation + $base_field_spec;
          $this->assertFieldAdditionRemoval($field_spec);
        }
      }
    }

    // Test numeric types.
    foreach ([1, 5, 10, 40, 65] as $precision) {
      foreach ([0, 2, 10, 30] as $scale) {
        // Skip combinations where precision is smaller than scale.
        if ($precision <= $scale) {
          continue;
        }

        $base_field_spec = [
          'type' => 'numeric',
          'scale' => $scale,
          'precision' => $precision,
        ];
        $variations = [
          ['not null' => FALSE],
          ['not null' => FALSE, 'default' => 7],
          ['not null' => TRUE, 'initial' => 1],
          ['not null' => TRUE, 'initial' => 1, 'default' => 7],
          ['not null' => TRUE, 'initial_from_field' => 'serial_column'],
        ];

        foreach ($variations as $variation) {
          $field_spec = $variation + $base_field_spec;
          $this->assertFieldAdditionRemoval($field_spec);
        }
      }
    }
  }

  /**
   * Asserts that a given field can be added and removed from a table.
   *
   * The addition test covers both defining a field of a given specification
   * when initially creating at table and extending an existing table.
   *
   * @param array $field_spec
   *   The schema specification of the field.
   *
   * @internal
   */
  protected function assertFieldAdditionRemoval(array $field_spec): void {
    // Try creating the field on a new table.
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = [
      'fields' => [
        'serial_column' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'test_nullable_field' => ['type' => 'int', 'not null' => FALSE],
        'test_field' => $field_spec,
      ],
      'primary key' => ['serial_column'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $field_spec);

    // Clean-up.
    $this->schema->dropTable($table_name);

    // Try adding a field to an existing table.
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = [
      'fields' => [
        'serial_column' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'test_nullable_field' => ['type' => 'int', 'not null' => FALSE],
      ],
      'primary key' => ['serial_column'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    // Insert some rows to the table to test the handling of initial values.
    for ($i = 0; $i < 3; $i++) {
      $this->connection
        ->insert($table_name)
        ->useDefaults(['serial_column'])
        ->fields(['test_nullable_field' => 100])
        ->execute();
    }

    // Add another row with no value for the 'test_nullable_field' column.
    $this->connection
      ->insert($table_name)
      ->useDefaults(['serial_column'])
      ->execute();

    $this->schema->addField($table_name, 'test_field', $field_spec);

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $field_spec);

    // Clean-up.
    $this->schema->dropField($table_name, 'test_field');

    // Add back the field and then try to delete a field which is also a primary
    // key.
    $this->schema->addField($table_name, 'test_field', $field_spec);
    $this->schema->dropField($table_name, 'serial_column');
    $this->schema->dropTable($table_name);
  }

  /**
   * Asserts that a newly added field has the correct characteristics.
   *
   * @internal
   */
  protected function assertFieldCharacteristics(string $table_name, string $field_name, array $field_spec): void {
    // Check that the initial value has been registered.
    if (isset($field_spec['initial'])) {
      // There should be no row with a value different then $field_spec['initial'].
      $count = $this->connection
        ->select($table_name)
        ->fields($table_name, ['serial_column'])
        ->condition($field_name, $field_spec['initial'], '<>')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals(0, $count, 'Initial values filled out.');
    }

    // Check that the initial value from another field has been registered.
    if (isset($field_spec['initial_from_field']) && !isset($field_spec['initial'])) {
      // There should be no row with a value different than
      // $field_spec['initial_from_field'].
      $count = $this->connection
        ->select($table_name)
        ->fields($table_name, ['serial_column'])
        ->where("[$table_name].[{$field_spec['initial_from_field']}] <> [$table_name].[$field_name]")
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals(0, $count, 'Initial values from another field filled out.');
    }
    elseif (isset($field_spec['initial_from_field']) && isset($field_spec['initial'])) {
      // There should be no row with a value different than '100'.
      $count = $this->connection
        ->select($table_name)
        ->fields($table_name, ['serial_column'])
        ->condition($field_name, 100, '<>')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEquals(0, $count, 'Initial values from another field or a default value filled out.');
    }

    // Check that the default value has been registered.
    if (isset($field_spec['default'])) {
      // Try inserting a row, and check the resulting value of the new column.
      $id = $this->connection
        ->insert($table_name)
        ->useDefaults(['serial_column'])
        ->execute();
      $field_value = $this->connection
        ->select($table_name)
        ->fields($table_name, [$field_name])
        ->condition('serial_column', $id)
        ->execute()
        ->fetchField();
      $this->assertEquals($field_spec['default'], $field_value, 'Default value registered.');
    }
  }

  /**
   * Tests various schema changes' effect on the table's primary key.
   *
   * @param array $initial_primary_key
   *   The initial primary key of the test table.
   * @param array $renamed_primary_key
   *   The primary key of the test table after renaming the test field.
   *
   * @dataProvider providerTestSchemaCreateTablePrimaryKey
   *
   * @covers ::addField
   * @covers ::changeField
   * @covers ::dropField
   * @covers ::findPrimaryKeyColumns
   */
  public function testSchemaChangePrimaryKey(array $initial_primary_key, array $renamed_primary_key): void {
    $find_primary_key_columns = new \ReflectionMethod(get_class($this->schema), 'findPrimaryKeyColumns');
    $find_primary_key_columns->setAccessible(TRUE);

    // Test making the field the primary key of the table upon creation.
    $table_name = 'test_table';
    $table_spec = [
      'fields' => [
        'test_field' => ['type' => 'int', 'not null' => TRUE],
        'other_test_field' => ['type' => 'int', 'not null' => TRUE],
      ],
      'primary key' => $initial_primary_key,
    ];
    $this->schema->createTable($table_name, $table_spec);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Change the field type and make sure the primary key stays in place.
    $this->schema->changeField($table_name, 'test_field', 'test_field', ['type' => 'varchar', 'length' => 32, 'not null' => TRUE]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Add some data and change the field type back, to make sure that changing
    // the type leaves the primary key in place even with existing data.
    $this->connection
      ->insert($table_name)
      ->fields(['test_field' => 1, 'other_test_field' => 2])
      ->execute();
    $this->schema->changeField($table_name, 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Make sure that adding the primary key can be done as part of changing
    // a field, as well.
    $this->schema->dropPrimaryKey($table_name);
    $this->assertEquals([], $find_primary_key_columns->invoke($this->schema, $table_name));
    $this->schema->changeField($table_name, 'test_field', 'test_field', ['type' => 'int', 'not null' => TRUE], ['primary key' => $initial_primary_key]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Rename the field and make sure the primary key was updated.
    $this->schema->changeField($table_name, 'test_field', 'test_field_renamed', ['type' => 'int', 'not null' => TRUE]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field_renamed'));
    $this->assertEquals($renamed_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Drop the field and make sure the primary key was dropped, as well.
    $this->schema->dropField($table_name, 'test_field_renamed');
    $this->assertFalse($this->schema->fieldExists($table_name, 'test_field_renamed'));
    $this->assertEquals([], $find_primary_key_columns->invoke($this->schema, $table_name));

    // Add the field again and make sure adding the primary key can be done at
    // the same time.
    $this->schema->addField($table_name, 'test_field', ['type' => 'int', 'default' => 0, 'not null' => TRUE], ['primary key' => $initial_primary_key]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));

    // Drop the field again and explicitly add a primary key.
    $this->schema->dropField($table_name, 'test_field');
    $this->schema->addPrimaryKey($table_name, ['other_test_field']);
    $this->assertFalse($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals(['other_test_field'], $find_primary_key_columns->invoke($this->schema, $table_name));

    // Test that adding a field with a primary key will work even with a
    // pre-existing primary key.
    $this->schema->addField($table_name, 'test_field', ['type' => 'int', 'default' => 0, 'not null' => TRUE], ['primary key' => $initial_primary_key]);
    $this->assertTrue($this->schema->fieldExists($table_name, 'test_field'));
    $this->assertEquals($initial_primary_key, $find_primary_key_columns->invoke($this->schema, $table_name));
  }

  /**
   * Provides test cases for SchemaTest::testSchemaCreateTablePrimaryKey().
   *
   * @return array
   *   An array of test cases for SchemaTest::testSchemaCreateTablePrimaryKey().
   */
  public function providerTestSchemaCreateTablePrimaryKey() {
    $tests = [];

    $tests['simple_primary_key'] = [
      'initial_primary_key' => ['test_field'],
      'renamed_primary_key' => ['test_field_renamed'],
    ];
    $tests['composite_primary_key'] = [
      'initial_primary_key' => ['test_field', 'other_test_field'],
      'renamed_primary_key' => ['test_field_renamed', 'other_test_field'],
    ];
    $tests['composite_primary_key_different_order'] = [
      'initial_primary_key' => ['other_test_field', 'test_field'],
      'renamed_primary_key' => ['other_test_field', 'test_field_renamed'],
    ];

    return $tests;
  }

  /**
   * Tests an invalid field specification as a primary key on table creation.
   */
  public function testInvalidPrimaryKeyOnTableCreation(): void {
    // Test making an invalid field the primary key of the table upon creation.
    $table_name = 'test_table';
    $table_spec = [
      'fields' => [
        'test_field' => ['type' => 'int'],
      ],
      'primary key' => ['test_field'],
    ];
    $this->expectException(SchemaException::class);
    $this->expectExceptionMessage("The 'test_field' field specification does not define 'not null' as TRUE.");
    $this->schema->createTable($table_name, $table_spec);
  }

  /**
   * Tests converting an int to a serial when the int column has data.
   */
  public function testChangePrimaryKeyToSerial(): void {
    // Test making an invalid field the primary key of the table upon creation.
    $table_name = 'test_table';
    $table_spec = [
      'fields' => [
        'test_field' => ['type' => 'int', 'not null' => TRUE],
        'test_field_string'  => ['type' => 'varchar', 'length' => 20],
      ],
      'primary key' => ['test_field'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    $this->tryInsertExpectsIntegrityConstraintViolationException($table_name);

    // @todo https://www.drupal.org/project/drupal/issues/3222127 Change the
    //   first item to 0 to test changing a field with 0 to a serial.
    // Create 8 rows in the table. Note that the 5 value is deliberately
    // omitted.
    foreach ([1, 2, 3, 4, 6, 7, 8, 9] as $value) {
      $this->connection
        ->insert($table_name)
        ->fields(['test_field' => $value])
        ->execute();
    }
    $this->schema->changeField($table_name, 'test_field', 'test_field', ['type' => 'serial', 'not null' => TRUE]);

    $data = $this->connection
      ->select($table_name)
      ->fields($table_name, ['test_field'])
      ->execute()
      ->fetchCol();
    $this->assertEquals([1, 2, 3, 4, 6, 7, 8, 9], array_values($data));

    try {
      $this->connection
        ->insert($table_name)
        ->fields(['test_field' => 1])
        ->execute();
      $this->fail('Expected IntegrityConstraintViolationException not thrown');
    }
    catch (IntegrityConstraintViolationException $e) {
    }

    // Ensure auto numbering now works.
    $id = $this->connection
      ->insert($table_name)
      ->fields(['test_field_string' => 'test'])
      ->execute();
    $this->assertEquals(10, $id);
  }

  /**
   * Tests adding an invalid field specification as a primary key.
   */
  public function testInvalidPrimaryKeyAddition(): void {
    // Test adding a new invalid field to the primary key.
    $table_name = 'test_table';
    $table_spec = [
      'fields' => [
        'test_field' => ['type' => 'int', 'not null' => TRUE],
      ],
      'primary key' => ['test_field'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    $this->expectException(SchemaException::class);
    $this->expectExceptionMessage("The 'new_test_field' field specification does not define 'not null' as TRUE.");
    $this->schema->addField($table_name, 'new_test_field', ['type' => 'int'], ['primary key' => ['test_field', 'new_test_field']]);
  }

  /**
   * Tests changing the primary key with an invalid field specification.
   */
  public function testInvalidPrimaryKeyChange(): void {
    // Test adding a new invalid field to the primary key.
    $table_name = 'test_table';
    $table_spec = [
      'fields' => [
        'test_field' => ['type' => 'int', 'not null' => TRUE],
      ],
      'primary key' => ['test_field'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    $this->expectException(SchemaException::class);
    $this->expectExceptionMessage("The 'changed_test_field' field specification does not define 'not null' as TRUE.");
    $this->schema->dropPrimaryKey($table_name);
    $this->schema->changeField($table_name, 'test_field', 'changed_test_field', ['type' => 'int'], ['primary key' => ['changed_test_field']]);
  }

  /**
   * Tests changing columns between types with default and initial values.
   */
  public function testSchemaChangeFieldDefaultInitial(): void {
    $field_specs = [
      ['type' => 'int', 'size' => 'normal', 'not null' => FALSE],
      ['type' => 'int', 'size' => 'normal', 'not null' => TRUE, 'initial' => 1, 'default' => 17],
      ['type' => 'float', 'size' => 'normal', 'not null' => FALSE],
      ['type' => 'float', 'size' => 'normal', 'not null' => TRUE, 'initial' => 1, 'default' => 7.3],
      ['type' => 'numeric', 'scale' => 2, 'precision' => 10, 'not null' => FALSE],
      ['type' => 'numeric', 'scale' => 2, 'precision' => 10, 'not null' => TRUE, 'initial' => 1, 'default' => 7],
    ];

    foreach ($field_specs as $i => $old_spec) {
      foreach ($field_specs as $j => $new_spec) {
        if ($i === $j) {
          // Do not change a field into itself.
          continue;
        }
        $this->assertFieldChange($old_spec, $new_spec);
      }
    }

    $field_specs = [
      ['type' => 'varchar_ascii', 'length' => '255'],
      ['type' => 'varchar', 'length' => '255'],
      ['type' => 'text'],
      ['type' => 'blob', 'size' => 'big'],
    ];

    foreach ($field_specs as $i => $old_spec) {
      foreach ($field_specs as $j => $new_spec) {
        if ($i === $j) {
          // Do not change a field into itself.
          continue;
        }
        // Note if the serialized data contained an object this would fail on
        // Postgres.
        // @see https://www.drupal.org/node/1031122
        $this->assertFieldChange($old_spec, $new_spec, serialize(['string' => "This \n has \\\\ some backslash \"*string action.\\n"]));
      }
    }

  }

  /**
   * Asserts that a field can be changed from one spec to another.
   *
   * @param array $old_spec
   *   The beginning field specification.
   * @param array $new_spec
   *   The ending field specification.
   * @param mixed $test_data
   *   (optional) A test value to insert and test, if specified.
   *
   * @internal
   */
  protected function assertFieldChange(array $old_spec, array $new_spec, $test_data = NULL): void {
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = [
      'fields' => [
        'serial_column' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'test_field' => $old_spec,
      ],
      'primary key' => ['serial_column'],
    ];
    $this->schema->createTable($table_name, $table_spec);

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $old_spec);

    // Remove inserted rows.
    $this->connection->truncate($table_name)->execute();

    if ($test_data) {
      $id = $this->connection
        ->insert($table_name)
        ->fields(['test_field'], [$test_data])
        ->execute();
    }

    // Change the field.
    $this->schema->changeField($table_name, 'test_field', 'test_field', $new_spec);

    if ($test_data) {
      $field_value = $this->connection
        ->select($table_name)
        ->fields($table_name, ['test_field'])
        ->condition('serial_column', $id)
        ->execute()
        ->fetchField();
      $this->assertSame($test_data, $field_value);
    }

    // Check the field was changed.
    $this->assertFieldCharacteristics($table_name, 'test_field', $new_spec);

    // Clean-up.
    $this->schema->dropTable($table_name);
  }

  /**
   * @covers ::findPrimaryKeyColumns
   */
  public function testFindPrimaryKeyColumns(): void {
    $method = new \ReflectionMethod(get_class($this->schema), 'findPrimaryKeyColumns');
    $method->setAccessible(TRUE);

    // Test with single column primary key.
    $this->schema->createTable('table_with_pk_0', [
      'description' => 'Table with primary key.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ]);
    $this->assertSame(['id'], $method->invoke($this->schema, 'table_with_pk_0'));

    // Test with multiple column primary key.
    $this->schema->createTable('table_with_pk_1', [
      'description' => 'Table with primary key with multiple columns.',
      'fields' => [
        'id0'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'id1'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id0', 'id1'],
    ]);
    $this->assertSame(['id0', 'id1'], $method->invoke($this->schema, 'table_with_pk_1'));

    // Test with multiple column primary key and not being the first column of
    // the table definition.
    $this->schema->createTable('table_with_pk_2', [
      'description' => 'Table with primary key with multiple columns at the end and in reverted sequence.',
      'fields' => [
        'test_field_1'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field_2'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'id3'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'id4'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id4', 'id3'],
    ]);
    $this->assertSame(['id4', 'id3'], $method->invoke($this->schema, 'table_with_pk_2'));

    // Test with multiple column primary key in a different order. For the
    // PostgreSQL and the SQLite drivers is sorting used to get the primary key
    // columns in the right order.
    $this->schema->createTable('table_with_pk_3', [
      'description' => 'Table with primary key with multiple columns at the end and in reverted sequence.',
      'fields' => [
        'test_field_1'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field_2'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'id3'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'id4'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id3', 'test_field_2', 'id4'],
    ]);
    $this->assertSame(['id3', 'test_field_2', 'id4'], $method->invoke($this->schema, 'table_with_pk_3'));

    // Test with table without a primary key.
    $this->schema->createTable('table_without_pk_1', [
      'description' => 'Table without primary key.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
    ]);
    $this->assertSame([], $method->invoke($this->schema, 'table_without_pk_1'));

    // Test with table with an empty primary key.
    $this->schema->createTable('table_without_pk_2', [
      'description' => 'Table without primary key.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => [],
    ]);
    $this->assertSame([], $method->invoke($this->schema, 'table_without_pk_2'));

    // Test with non existing table.
    $this->assertFalse($method->invoke($this->schema, 'non_existing_table'));
  }

  /**
   * Tests the findTables() method.
   */
  public function testFindTables(): void {
    // We will be testing with three tables.
    $test_schema = Database::getConnection()->schema();

    // Create the tables.
    $table_specification = [
      'description' => 'Test table.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
      ],
    ];
    $test_schema->createTable('test_1_table', $table_specification);
    $test_schema->createTable('test_2_table', $table_specification);
    $test_schema->createTable('the_third_table', $table_specification);

    // Check the "all tables" syntax.
    $tables = $test_schema->findTables('%');
    sort($tables);
    $expected = [
      // The 'config' table is added by
      // \Drupal\KernelTests\KernelTestBase::containerBuild().
      'config',
      'test_1_table',
      // This table uses a per-table prefix, yet it is returned as un-prefixed.
      'test_2_table',
      'the_third_table',
    ];
    $this->assertEquals($expected, $tables, 'All tables were found.');

    // Check the restrictive syntax.
    $tables = $test_schema->findTables('test_%');
    sort($tables);
    $expected = [
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEquals($expected, $tables, 'Two tables were found.');

    // Check '_' and '%' wildcards.
    $test_schema->createTable('test3table', $table_specification);
    $test_schema->createTable('test4', $table_specification);
    $test_schema->createTable('testTable', $table_specification);
    $test_schema->createTable('test', $table_specification);

    $tables = $test_schema->findTables('test%');
    sort($tables);
    $expected = [
      'test',
      'test3table',
      'test4',
      'testTable',
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEquals($expected, $tables, 'All "test" prefixed tables were found.');

    $tables = $test_schema->findTables('test_%');
    sort($tables);
    $expected = [
      'test3table',
      'test4',
      'testTable',
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEquals($expected, $tables, 'All "/^test..*?/" tables were found.');

    $tables = $test_schema->findTables('test%table');
    sort($tables);
    $expected = [
      'test3table',
      'testTable',
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEquals($expected, $tables, 'All "/^test.*?table/" tables were found.');

    $tables = $test_schema->findTables('test_%table');
    sort($tables);
    $expected = [
      'test3table',
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEquals($expected, $tables, 'All "/^test..*?table/" tables were found.');

    $tables = $test_schema->findTables('test_');
    sort($tables);
    $expected = [
      'test4',
    ];
    $this->assertEquals($expected, $tables, 'All "/^test./" tables were found.');
  }

  /**
   * Tests handling of uppercase table names.
   */
  public function testUpperCaseTableName(): void {
    $table_name = 'A_UPPER_CASE_TABLE_NAME';

    // Create the tables.
    $table_specification = [
      'description' => 'Test table.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
      ],
    ];
    $this->schema->createTable($table_name, $table_specification);

    $this->assertTrue($this->schema->tableExists($table_name), 'Table with uppercase table name exists');
    $this->assertContains($table_name, $this->schema->findTables('%'));
    $this->assertTrue($this->schema->dropTable($table_name), 'Table with uppercase table name dropped');
  }

  /**
   * Tests default values after altering table.
   */
  public function testDefaultAfterAlter(): void {
    $table_name = 'test_table';

    // Create the table.
    $table_specification = [
      'description' => 'Test table.',
      'fields' => [
        'column1'  => [
          'type' => 'int',
          'default' => NULL,
        ],
        'column2'  => [
          'type' => 'varchar',
          'length' => 20,
          'default' => NULL,
        ],
        'column3'  => [
          'type' => 'int',
          'default' => 200,
        ],
        'column4'  => [
          'type' => 'float',
          'default' => 1.23,
        ],
        'column5'  => [
          'type' => 'varchar',
          'length' => 20,
          'default' => "'s o'clock'",
        ],
        'column6'  => [
          'type' => 'varchar',
          'length' => 20,
          'default' => "o'clock",
        ],
        'column7'  => [
          'type' => 'varchar',
          'length' => 20,
          'default' => 'default value',
        ],
      ],
    ];
    $this->schema->createTable($table_name, $table_specification);

    // Insert a row and check that columns have the expected default values.
    $this->connection->insert($table_name)->fields(['column1' => 1])->execute();
    $result = $this->connection->select($table_name, 't')->fields('t', ['column2', 'column3', 'column4', 'column5', 'column6', 'column7'])->condition('column1', 1)->execute()->fetchObject();
    $this->assertNull($result->column2);
    $this->assertSame('200', $result->column3);
    $this->assertSame('1.23', $result->column4);
    $this->assertSame("'s o'clock'", $result->column5);
    $this->assertSame("o'clock", $result->column6);
    $this->assertSame('default value', $result->column7);

    // Force SQLite schema to create a new table and copy data by adding a not
    // field with an initial value.
    $this->schema->addField('test_table', 'new_column', ['type' => 'varchar', 'length' => 20, 'not null' => TRUE, 'description' => 'Added new column', 'initial' => 'test']);

    // Test that the columns default values are still correct.
    $this->connection->insert($table_name)->fields(['column1' => 2, 'new_column' => 'value'])->execute();
    $result = $this->connection->select($table_name, 't')->fields('t', ['column2', 'column3', 'column4', 'column5', 'column6', 'column7'])->condition('column1', 2)->execute()->fetchObject();
    $this->assertNull($result->column2);
    $this->assertSame('200', $result->column3);
    $this->assertSame('1.23', $result->column4);
    $this->assertSame("'s o'clock'", $result->column5);
    $this->assertSame("o'clock", $result->column6);
    $this->assertSame('default value', $result->column7);
  }

  /**
   * Tests handling with reserved keywords for naming tables, fields and more.
   */
  public function testReservedKeywordsForNaming(): void {
    $table_specification = [
      'description' => 'A test table with an ANSI reserved keywords for naming.',
      'fields' => [
        'primary' => [
          'description' => 'Simple unique ID.',
          'type' => 'int',
          'not null' => TRUE,
        ],
        'update' => [
          'description' => 'A column with reserved name.',
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'primary key' => ['primary'],
      'unique keys' => [
        'having' => ['update'],
      ],
      'indexes' => [
        'in' => ['primary', 'update'],
      ],
    ];

    // Creating a table.
    $table_name = 'select';
    $this->schema->createTable($table_name, $table_specification);
    $this->assertTrue($this->schema->tableExists($table_name));

    // Finding all tables.
    $tables = $this->schema->findTables('%');
    sort($tables);
    $this->assertEquals(['config', 'select'], $tables);

    // Renaming a table.
    $table_name_new = 'from';
    $this->schema->renameTable($table_name, $table_name_new);
    $this->assertFalse($this->schema->tableExists($table_name));
    $this->assertTrue($this->schema->tableExists($table_name_new));

    // Adding a field.
    $field_name = 'delete';
    $this->schema->addField($table_name_new, $field_name, ['type' => 'int', 'not null' => TRUE]);
    $this->assertTrue($this->schema->fieldExists($table_name_new, $field_name));

    // Dropping a primary key.
    $this->schema->dropPrimaryKey($table_name_new);

    // Adding a primary key.
    $this->schema->addPrimaryKey($table_name_new, [$field_name]);

    // Check the primary key columns.
    $find_primary_key_columns = new \ReflectionMethod(get_class($this->schema), 'findPrimaryKeyColumns');
    $this->assertEquals([$field_name], $find_primary_key_columns->invoke($this->schema, $table_name_new));

    // Dropping a primary key.
    $this->schema->dropPrimaryKey($table_name_new);

    // Changing a field.
    $field_name_new = 'where';
    $this->schema->changeField($table_name_new, $field_name, $field_name_new, ['type' => 'int', 'not null' => FALSE]);
    $this->assertFalse($this->schema->fieldExists($table_name_new, $field_name));
    $this->assertTrue($this->schema->fieldExists($table_name_new, $field_name_new));

    // Adding an unique key
    $unique_key_name = $unique_key_introspect_name = 'unique';
    $this->schema->addUniqueKey($table_name_new, $unique_key_name, [$field_name_new]);

    // Check the unique key columns.
    $introspect_index_schema = new \ReflectionMethod(get_class($this->schema), 'introspectIndexSchema');
    $this->assertEquals([$field_name_new], $introspect_index_schema->invoke($this->schema, $table_name_new)['unique keys'][$unique_key_introspect_name]);

    // Dropping an unique key
    $this->schema->dropUniqueKey($table_name_new, $unique_key_name);

    // Dropping a field.
    $this->schema->dropField($table_name_new, $field_name_new);
    $this->assertFalse($this->schema->fieldExists($table_name_new, $field_name_new));

    // Adding an index.
    $index_name = $index_introspect_name = 'index';
    $this->schema->addIndex($table_name_new, $index_name, ['update'], $table_specification);
    $this->assertTrue($this->schema->indexExists($table_name_new, $index_name));

    // Check the index columns.
    $this->assertEquals(['update'], $introspect_index_schema->invoke($this->schema, $table_name_new)['indexes'][$index_introspect_name]);

    // Dropping an index.
    $this->schema->dropIndex($table_name_new, $index_name);
    $this->assertFalse($this->schema->indexExists($table_name_new, $index_name));

    // Dropping a table.
    $this->schema->dropTable($table_name_new);
    $this->assertFalse($this->schema->tableExists($table_name_new));
  }

}
