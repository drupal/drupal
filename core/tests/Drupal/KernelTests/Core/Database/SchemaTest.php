<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;

/**
 * Tests table creation and modification via the schema API.
 *
 * @group Database
 */
class SchemaTest extends KernelTestBase {

  /**
   * A global counter for table and field creation.
   */
  protected $counter;

  /**
   * Tests database interactions.
   */
  function testSchema() {
    // Try creating a table.
    $table_specification = array(
      'description' => 'Schema table description may contain "quotes" and could be long—very long indeed.',
      'fields' => array(
        'id'  => array(
          'type' => 'int',
          'default' => NULL,
        ),
        'test_field'  => array(
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'Schema table description may contain "quotes" and could be long—very long indeed. There could be "multiple quoted regions".',
        ),
        'test_field_string'  => array(
          'type' => 'varchar',
          'length' => 20,
          'not null' => TRUE,
          'default' => "'\"funky default'\"",
          'description' => 'Schema column description for string.',
        ),
        'test_field_string_ascii'  => array(
          'type' => 'varchar_ascii',
          'length' => 255,
          'description' => 'Schema column description for ASCII string.',
        ),
      ),
    );
    db_create_table('test_table', $table_specification);

    // Assert that the table exists.
    $this->assertTrue(db_table_exists('test_table'), 'The table exists.');

    // Assert that the table comment has been set.
    $this->checkSchemaComment($table_specification['description'], 'test_table');

    // Assert that the column comment has been set.
    $this->checkSchemaComment($table_specification['fields']['test_field']['description'], 'test_table', 'test_field');

    if (Database::getConnection()->databaseType() == 'mysql') {
      // Make sure that varchar fields have the correct collation.
      $columns = db_query('SHOW FULL COLUMNS FROM {test_table}');
      foreach ($columns as $column) {
        if ($column->Field == 'test_field_string') {
          $string_check = ($column->Collation == 'utf8mb4_general_ci');
        }
        if ($column->Field == 'test_field_string_ascii') {
          $string_ascii_check = ($column->Collation == 'ascii_general_ci');
        }
      }
      $this->assertTrue(!empty($string_check), 'string field has the right collation.');
      $this->assertTrue(!empty($string_ascii_check), 'ASCII string field has the right collation.');
    }

    // An insert without a value for the column 'test_table' should fail.
    $this->assertFalse($this->tryInsert(), 'Insert without a default failed.');

    // Add a default value to the column.
    db_field_set_default('test_table', 'test_field', 0);
    // The insert should now succeed.
    $this->assertTrue($this->tryInsert(), 'Insert with a default succeeded.');

    // Remove the default.
    db_field_set_no_default('test_table', 'test_field');
    // The insert should fail again.
    $this->assertFalse($this->tryInsert(), 'Insert without a default failed.');

    // Test for fake index and test for the boolean result of indexExists().
    $index_exists = Database::getConnection()->schema()->indexExists('test_table', 'test_field');
    $this->assertIdentical($index_exists, FALSE, 'Fake index does not exists');
    // Add index.
    db_add_index('test_table', 'test_field', array('test_field'), $table_specification);
    // Test for created index and test for the boolean result of indexExists().
    $index_exists = Database::getConnection()->schema()->indexExists('test_table', 'test_field');
    $this->assertIdentical($index_exists, TRUE, 'Index created.');

    // Rename the table.
    db_rename_table('test_table', 'test_table2');

    // Index should be renamed.
    $index_exists = Database::getConnection()->schema()->indexExists('test_table2', 'test_field');
    $this->assertTrue($index_exists, 'Index was renamed.');

    // We need the default so that we can insert after the rename.
    db_field_set_default('test_table2', 'test_field', 0);
    $this->assertFalse($this->tryInsert(), 'Insert into the old table failed.');
    $this->assertTrue($this->tryInsert('test_table2'), 'Insert into the new table succeeded.');

    // We should have successfully inserted exactly two rows.
    $count = db_query('SELECT COUNT(*) FROM {test_table2}')->fetchField();
    $this->assertEqual($count, 2, 'Two fields were successfully inserted.');

    // Try to drop the table.
    db_drop_table('test_table2');
    $this->assertFalse(db_table_exists('test_table2'), 'The dropped table does not exist.');

    // Recreate the table.
    db_create_table('test_table', $table_specification);
    db_field_set_default('test_table', 'test_field', 0);
    db_add_field('test_table', 'test_serial', array('type' => 'int', 'not null' => TRUE, 'default' => 0, 'description' => 'Added column description.'));

    // Assert that the column comment has been set.
    $this->checkSchemaComment('Added column description.', 'test_table', 'test_serial');

    // Change the new field to a serial column.
    db_change_field('test_table', 'test_serial', 'test_serial', array('type' => 'serial', 'not null' => TRUE, 'description' => 'Changed column description.'), array('primary key' => array('test_serial')));

    // Assert that the column comment has been set.
    $this->checkSchemaComment('Changed column description.', 'test_table', 'test_serial');

    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max1 = db_query('SELECT MAX(test_serial) FROM {test_table}')->fetchField();
    $this->assertTrue($this->tryInsert(), 'Insert with a serial succeeded.');
    $max2 = db_query('SELECT MAX(test_serial) FROM {test_table}')->fetchField();
    $this->assertTrue($max2 > $max1, 'The serial is monotone.');

    $count = db_query('SELECT COUNT(*) FROM {test_table}')->fetchField();
    $this->assertEqual($count, 2, 'There were two rows.');

    // Test renaming of keys and constraints.
    db_drop_table('test_table');
    $table_specification = array(
      'fields' => array(
        'id'  => array(
          'type' => 'serial',
          'not null' => TRUE,
        ),
        'test_field'  => array(
          'type' => 'int',
          'default' => 0,
        ),
      ),
      'primary key' => array('id'),
      'unique keys' => array(
        'test_field' => array('test_field'),
      ),
    );
    db_create_table('test_table', $table_specification);

    // Tests for indexes are Database specific.
    $db_type = Database::getConnection()->databaseType();

    // Test for existing primary and unique keys.
    switch ($db_type) {
      case 'pgsql':
        $primary_key_exists = Database::getConnection()->schema()->constraintExists('test_table', '__pkey');
        $unique_key_exists = Database::getConnection()->schema()->constraintExists('test_table', 'test_field' . '__key');
        break;
      case 'sqlite':
        // SQLite does not create a standalone index for primary keys.
        $primary_key_exists = TRUE;
        $unique_key_exists = Database::getConnection()->schema()->indexExists('test_table', 'test_field');
        break;
      default:
        $primary_key_exists = Database::getConnection()->schema()->indexExists('test_table', 'PRIMARY');
        $unique_key_exists = Database::getConnection()->schema()->indexExists('test_table', 'test_field');
        break;
    }
    $this->assertIdentical($primary_key_exists, TRUE, 'Primary key created.');
    $this->assertIdentical($unique_key_exists, TRUE, 'Unique key created.');

    db_rename_table('test_table', 'test_table2');

    // Test for renamed primary and unique keys.
    switch ($db_type) {
      case 'pgsql':
        $renamed_primary_key_exists = Database::getConnection()->schema()->constraintExists('test_table2', '__pkey');
        $renamed_unique_key_exists = Database::getConnection()->schema()->constraintExists('test_table2', 'test_field' . '__key');
        break;
      case 'sqlite':
        // SQLite does not create a standalone index for primary keys.
        $renamed_primary_key_exists = TRUE;
        $renamed_unique_key_exists = Database::getConnection()->schema()->indexExists('test_table2', 'test_field');
        break;
      default:
        $renamed_primary_key_exists = Database::getConnection()->schema()->indexExists('test_table2', 'PRIMARY');
        $renamed_unique_key_exists = Database::getConnection()->schema()->indexExists('test_table2', 'test_field');
        break;
    }
    $this->assertIdentical($renamed_primary_key_exists, TRUE, 'Primary key was renamed.');
    $this->assertIdentical($renamed_unique_key_exists, TRUE, 'Unique key was renamed.');

    // For PostgreSQL check in addition that sequence was renamed.
    if ($db_type == 'pgsql') {
      // Get information about new table.
      $info = Database::getConnection()->schema()->queryTableInformation('test_table2');
      $sequence_name = Database::getConnection()->schema()->prefixNonTable('test_table2', 'id', 'seq');
      $this->assertEqual($sequence_name, current($info->sequences), 'Sequence was renamed.');
    }

    // Use database specific data type and ensure that table is created.
    $table_specification = array(
      'description' => 'Schema table description.',
      'fields' => array(
        'timestamp'  => array(
          'mysql_type' => 'timestamp',
          'pgsql_type' => 'timestamp',
          'sqlite_type' => 'datetime',
          'not null' => FALSE,
          'default' => NULL,
        ),
      ),
    );
    try {
      db_create_table('test_timestamp', $table_specification);
    }
    catch (\Exception $e) {}
    $this->assertTrue(db_table_exists('test_timestamp'), 'Table with database specific datatype was created.');
  }

  /**
   * Tests that indexes on string fields are limited to 191 characters on MySQL.
   *
   * @see \Drupal\Core\Database\Driver\mysql\Schema::getNormalizedIndexes()
   */
  function testIndexLength() {
    if (Database::getConnection()->databaseType() != 'mysql') {
      return;
    }
    $table_specification = array(
      'fields' => array(
        'id'  => array(
          'type' => 'int',
          'default' => NULL,
        ),
        'test_field_text'  => array(
          'type' => 'text',
          'not null' => TRUE,
        ),
        'test_field_string_long'  => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'test_field_string_ascii_long'  => array(
          'type' => 'varchar_ascii',
          'length' => 255,
        ),
        'test_field_string_short'  => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ),
      ),
      'indexes' => array(
        'test_regular' => array(
          'test_field_text',
          'test_field_string_long',
          'test_field_string_ascii_long',
          'test_field_string_short',
        ),
        'test_length' => array(
          array('test_field_text', 128),
          array('test_field_string_long', 128),
          array('test_field_string_ascii_long', 128),
          array('test_field_string_short', 128),
        ),
        'test_mixed' => array(
          array('test_field_text', 200),
          'test_field_string_long',
          array('test_field_string_ascii_long', 200),
          'test_field_string_short',
        ),
      ),
    );
    db_create_table('test_table_index_length', $table_specification);

    $schema_object = Database::getConnection()->schema();

    // Ensure expected exception thrown when adding index with missing info.
    $expected_exception_message = "MySQL needs the 'test_field_text' field specification in order to normalize the 'test_regular' index";
    $missing_field_spec = $table_specification;
    unset($missing_field_spec['fields']['test_field_text']);
    try {
      $schema_object->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $missing_field_spec);
      $this->fail('SchemaException not thrown when adding index with missing information.');
    }
    catch (SchemaException $e) {
      $this->assertEqual($expected_exception_message, $e->getMessage());
    }

    // Add a separate index.
    $schema_object->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $table_specification);
    $table_specification_with_new_index = $table_specification;
    $table_specification_with_new_index['indexes']['test_separate'] = [['test_field_text', 200]];

    // Ensure that the exceptions of addIndex are thrown as expected.

    try {
      $schema_object->addIndex('test_table_index_length', 'test_separate', [['test_field_text', 200]], $table_specification);
      $this->fail('\Drupal\Core\Database\SchemaObjectExistsException exception missed.');
    }
    catch (SchemaObjectExistsException $e) {
      $this->pass('\Drupal\Core\Database\SchemaObjectExistsException thrown when index already exists.');
    }

    try {
      $schema_object->addIndex('test_table_non_existing', 'test_separate', [['test_field_text', 200]], $table_specification);
      $this->fail('\Drupal\Core\Database\SchemaObjectDoesNotExistException exception missed.');
    }
    catch (SchemaObjectDoesNotExistException $e) {
      $this->pass('\Drupal\Core\Database\SchemaObjectDoesNotExistException thrown when index already exists.');
    }

    // Get index information.
    $results = db_query('SHOW INDEX FROM {test_table_index_length}');
    $expected_lengths = array(
      'test_regular' => array(
        'test_field_text' => 191,
        'test_field_string_long' => 191,
        'test_field_string_ascii_long' => NULL,
        'test_field_string_short' => NULL,
      ),
      'test_length' => array(
        'test_field_text' => 128,
        'test_field_string_long' => 128,
        'test_field_string_ascii_long' => 128,
        'test_field_string_short' => NULL,
      ),
      'test_mixed' => array(
        'test_field_text' => 191,
        'test_field_string_long' => 191,
        'test_field_string_ascii_long' => 200,
        'test_field_string_short' => NULL,
      ),
      'test_separate' => array(
        'test_field_text' => 191,
      ),
    );

    // Count the number of columns defined in the indexes.
    $column_count = 0;
    foreach ($table_specification_with_new_index['indexes'] as $index) {
      foreach ($index as $field) {
        $column_count++;
      }
    }
    $test_count = 0;
    foreach ($results as $result) {
      $this->assertEqual($result->Sub_part, $expected_lengths[$result->Key_name][$result->Column_name], 'Index length matches expected value.');
      $test_count++;
    }
    $this->assertEqual($test_count, $column_count, 'Number of tests matches expected value.');
  }

  /**
   * Tests inserting data into an existing table.
   *
   * @param $table
   *   The database table to insert data into.
   *
   * @return
   *   TRUE if the insert succeeded, FALSE otherwise.
   */
  function tryInsert($table = 'test_table') {
    try {
      db_insert($table)
        ->fields(array('id' => mt_rand(10, 20)))
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks that a table or column comment matches a given description.
   *
   * @param $description
   *   The asserted description.
   * @param $table
   *   The table to test.
   * @param $column
   *   Optional column to test.
   */
  function checkSchemaComment($description, $table, $column = NULL) {
    if (method_exists(Database::getConnection()->schema(), 'getComment')) {
      $comment = Database::getConnection()->schema()->getComment($table, $column);
      // The schema comment truncation for mysql is different.
      if (Database::getConnection()->databaseType() == 'mysql') {
        $max_length = $column ? 255 : 60;
        $description = Unicode::truncate($description, $max_length, TRUE, TRUE);
      }
      $this->assertEqual($comment, $description, 'The comment matches the schema description.');
    }
  }

  /**
   * Tests creating unsigned columns and data integrity thereof.
   */
  function testUnsignedColumns() {
    // First create the table with just a serial column.
    $table_name = 'unsigned_table';
    $table_spec = array(
      'fields' => array('serial_column' => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE)),
      'primary key' => array('serial_column'),
    );
    db_create_table($table_name, $table_spec);

    // Now set up columns for the other types.
    $types = array('int', 'float', 'numeric');
    foreach ($types as $type) {
      $column_spec = array('type' => $type, 'unsigned'=> TRUE);
      if ($type == 'numeric') {
        $column_spec += array('precision' => 10, 'scale' => 0);
      }
      $column_name = $type . '_column';
      $table_spec['fields'][$column_name] = $column_spec;
      db_add_field($table_name, $column_name, $column_spec);
    }

    // Finally, check each column and try to insert invalid values into them.
    foreach ($table_spec['fields'] as $column_name => $column_spec) {
      $this->assertTrue(db_field_exists($table_name, $column_name), format_string('Unsigned @type column was created.', array('@type' => $column_spec['type'])));
      $this->assertFalse($this->tryUnsignedInsert($table_name, $column_name), format_string('Unsigned @type column rejected a negative value.', array('@type' => $column_spec['type'])));
    }
  }

  /**
   * Tries to insert a negative value into columns defined as unsigned.
   *
   * @param $table_name
   *   The table to insert.
   * @param $column_name
   *   The column to insert.
   *
   * @return
   *   TRUE if the insert succeeded, FALSE otherwise.
   */
  function tryUnsignedInsert($table_name, $column_name) {
    try {
      db_insert($table_name)
        ->fields(array($column_name => -1))
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Tests adding columns to an existing table.
   */
  function testSchemaAddField() {
    // Test varchar types.
    foreach (array(1, 32, 128, 256, 512) as $length) {
      $base_field_spec = array(
        'type' => 'varchar',
        'length' => $length,
      );
      $variations = array(
        array('not null' => FALSE),
        array('not null' => FALSE, 'default' => '7'),
        array('not null' => FALSE, 'default' => substr('"thing"', 0, $length)),
        array('not null' => FALSE, 'default' => substr("\"'hing", 0, $length)),
        array('not null' => TRUE, 'initial' => 'd'),
        array('not null' => FALSE, 'default' => NULL),
        array('not null' => TRUE, 'initial' => 'd', 'default' => '7'),
      );

      foreach ($variations as $variation) {
        $field_spec = $variation + $base_field_spec;
        $this->assertFieldAdditionRemoval($field_spec);
      }
    }

    // Test int and float types.
    foreach (array('int', 'float') as $type) {
      foreach (array('tiny', 'small', 'medium', 'normal', 'big') as $size) {
        $base_field_spec = array(
          'type' => $type,
          'size' => $size,
        );
        $variations = array(
          array('not null' => FALSE),
          array('not null' => FALSE, 'default' => 7),
          array('not null' => TRUE, 'initial' => 1),
          array('not null' => TRUE, 'initial' => 1, 'default' => 7),
        );

        foreach ($variations as $variation) {
          $field_spec = $variation + $base_field_spec;
          $this->assertFieldAdditionRemoval($field_spec);
        }
      }
    }

    // Test numeric types.
    foreach (array(1, 5, 10, 40, 65) as $precision) {
      foreach (array(0, 2, 10, 30) as $scale) {
        // Skip combinations where precision is smaller than scale.
        if ($precision <= $scale) {
          continue;
        }

        $base_field_spec = array(
          'type' => 'numeric',
          'scale' => $scale,
          'precision' => $precision,
        );
        $variations = array(
          array('not null' => FALSE),
          array('not null' => FALSE, 'default' => 7),
          array('not null' => TRUE, 'initial' => 1),
          array('not null' => TRUE, 'initial' => 1, 'default' => 7),
        );

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
   * @param $field_spec
   *   The schema specification of the field.
   */
  protected function assertFieldAdditionRemoval($field_spec) {
    // Try creating the field on a new table.
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = array(
      'fields' => array(
        'serial_column' => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE),
        'test_field' => $field_spec,
      ),
      'primary key' => array('serial_column'),
    );
    db_create_table($table_name, $table_spec);
    $this->pass(format_string('Table %table created.', array('%table' => $table_name)));

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $field_spec);

    // Clean-up.
    db_drop_table($table_name);

    // Try adding a field to an existing table.
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = array(
      'fields' => array(
        'serial_column' => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE),
      ),
      'primary key' => array('serial_column'),
    );
    db_create_table($table_name, $table_spec);
    $this->pass(format_string('Table %table created.', array('%table' => $table_name)));

    // Insert some rows to the table to test the handling of initial values.
    for ($i = 0; $i < 3; $i++) {
      db_insert($table_name)
        ->useDefaults(array('serial_column'))
        ->execute();
    }

    db_add_field($table_name, 'test_field', $field_spec);
    $this->pass(format_string('Column %column created.', array('%column' => 'test_field')));

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $field_spec);

    // Clean-up.
    db_drop_field($table_name, 'test_field');

    // Add back the field and then try to delete a field which is also a primary
    // key.
    db_add_field($table_name, 'test_field', $field_spec);
    db_drop_field($table_name, 'serial_column');
    db_drop_table($table_name);
  }

  /**
   * Asserts that a newly added field has the correct characteristics.
   */
  protected function assertFieldCharacteristics($table_name, $field_name, $field_spec) {
    // Check that the initial value has been registered.
    if (isset($field_spec['initial'])) {
      // There should be no row with a value different then $field_spec['initial'].
      $count = db_select($table_name)
        ->fields($table_name, array('serial_column'))
        ->condition($field_name, $field_spec['initial'], '<>')
        ->countQuery()
        ->execute()
        ->fetchField();
      $this->assertEqual($count, 0, 'Initial values filled out.');
    }

    // Check that the default value has been registered.
    if (isset($field_spec['default'])) {
      // Try inserting a row, and check the resulting value of the new column.
      $id = db_insert($table_name)
        ->useDefaults(array('serial_column'))
        ->execute();
      $field_value = db_select($table_name)
        ->fields($table_name, array($field_name))
        ->condition('serial_column', $id)
        ->execute()
        ->fetchField();
      $this->assertEqual($field_value, $field_spec['default'], 'Default value registered.');
    }
  }

  /**
   * Tests changing columns between types.
   */
  function testSchemaChangeField() {
    $field_specs = array(
      array('type' => 'int', 'size' => 'normal', 'not null' => FALSE),
      array('type' => 'int', 'size' => 'normal', 'not null' => TRUE, 'initial' => 1, 'default' => 17),
      array('type' => 'float', 'size' => 'normal', 'not null' => FALSE),
      array('type' => 'float', 'size' => 'normal', 'not null' => TRUE, 'initial' => 1, 'default' => 7.3),
      array('type' => 'numeric', 'scale' => 2, 'precision' => 10, 'not null' => FALSE),
      array('type' => 'numeric', 'scale' => 2, 'precision' => 10, 'not null' => TRUE, 'initial' => 1, 'default' => 7),
    );

    foreach ($field_specs as $i => $old_spec) {
      foreach ($field_specs as $j => $new_spec) {
        if ($i === $j) {
          // Do not change a field into itself.
          continue;
        }
        $this->assertFieldChange($old_spec, $new_spec);
      }
    }

    $field_specs = array(
      array('type' => 'varchar_ascii', 'length' => '255'),
      array('type' => 'varchar', 'length' => '255'),
      array('type' => 'text'),
      array('type' => 'blob', 'size' => 'big'),
    );

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
   * @param $old_spec
   *   The beginning field specification.
   * @param $new_spec
   *   The ending field specification.
   */
  protected function assertFieldChange($old_spec, $new_spec, $test_data = NULL) {
    $table_name = 'test_table_' . ($this->counter++);
    $table_spec = array(
      'fields' => array(
        'serial_column' => array('type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE),
        'test_field' => $old_spec,
      ),
      'primary key' => array('serial_column'),
    );
    db_create_table($table_name, $table_spec);
    $this->pass(format_string('Table %table created.', array('%table' => $table_name)));

    // Check the characteristics of the field.
    $this->assertFieldCharacteristics($table_name, 'test_field', $old_spec);

    // Remove inserted rows.
    db_truncate($table_name)->execute();

    if ($test_data) {
      $id = db_insert($table_name)
        ->fields(['test_field'], [$test_data])
        ->execute();
    }

    // Change the field.
    db_change_field($table_name, 'test_field', 'test_field', $new_spec);

    if ($test_data) {
      $field_value = db_select($table_name)
        ->fields($table_name, ['test_field'])
        ->condition('serial_column', $id)
        ->execute()
        ->fetchField();
      $this->assertIdentical($field_value, $test_data);
    }

    // Check the field was changed.
    $this->assertFieldCharacteristics($table_name, 'test_field', $new_spec);

    // Clean-up.
    db_drop_table($table_name);
  }

  /**
   * Tests the findTables() method.
   */
  public function testFindTables() {
    // We will be testing with three tables, two of them using the default
    // prefix and the third one with an individually specified prefix.

    // Set up a new connection with different connection info.
    $connection_info = Database::getConnectionInfo();

    // Add per-table prefix to the second table.
    $new_connection_info = $connection_info['default'];
    $new_connection_info['prefix']['test_2_table'] = $new_connection_info['prefix']['default'] . '_shared_';
    Database::addConnectionInfo('test', 'default', $new_connection_info);

    Database::setActiveConnection('test');

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
    Database::getConnection()->schema()->createTable('test_1_table', $table_specification);
    Database::getConnection()->schema()->createTable('test_2_table', $table_specification);
    Database::getConnection()->schema()->createTable('the_third_table', $table_specification);

    // Check the "all tables" syntax.
    $tables = Database::getConnection()->schema()->findTables('%');
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
    $this->assertEqual($tables, $expected, 'All tables were found.');

    // Check the restrictive syntax.
    $tables = Database::getConnection()->schema()->findTables('test_%');
    sort($tables);
    $expected = [
      'test_1_table',
      'test_2_table',
    ];
    $this->assertEqual($tables, $expected, 'Two tables were found.');

    // Go back to the initial connection.
    Database::setActiveConnection('default');
  }

}
