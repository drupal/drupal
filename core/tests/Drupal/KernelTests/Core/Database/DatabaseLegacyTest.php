<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Truncate;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;

/**
 * Deprecation tests cases for the database layer.
 *
 * @group legacy
 */
class DatabaseLegacyTest extends DatabaseTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = ['database_test', 'system'];

  /**
   * Tests deprecation of the db_and() function.
   *
   * @expectedDeprecation db_and() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying an AND conjunction: new Condition('AND'), instead. See https://www.drupal.org/node/2993033
   */
  public function testDbAnd() {
    $this->assertInstanceOf(Condition::class, db_and());
  }

  /**
   * Tests deprecation of the db_condition() function.
   *
   * @expectedDeprecation db_condition() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying the desired conjunction: new Condition($conjunction), instead. See https://www.drupal.org/node/2993033
   */
  public function testDbCondition() {
    $this->assertInstanceOf(Condition::class, db_condition('AND'));
  }

  /**
   * Tests deprecation of the db_or() function.
   *
   * @expectedDeprecation db_or() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying an OR conjunction: new Condition('OR'), instead. See https://www.drupal.org/node/2993033
   */
  public function testDbOr() {
    $this->assertInstanceOf(Condition::class, db_or());
  }

  /**
   * Tests deprecation of the db_xor() function.
   *
   * @expectedDeprecation db_xor() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying a XOR conjunction: new Condition('XOR'), instead. See https://www.drupal.org/node/2993033
   */
  public function testDbXor() {
    $this->assertInstanceOf(Condition::class, db_xor());
  }

  /**
   * Tests the db_table_exists() function.
   *
   * @expectedDeprecation db_table_exists() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Use $injected_database->schema()->tableExists($table) instead. See https://www.drupal.org/node/2993033
   */
  public function testDbTableExists() {
    $this->assertTrue(db_table_exists('test'));
  }

  /**
   * Tests the db_find_tables() function.
   *
   * @expectedDeprecation db_find_tables() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Use $injected_database->schema()->findTables($table_expression) instead. See https://www.drupal.org/node/2993033
   */
  public function testDbFindTables() {
    $expected = [
      'test_people' => 'test_people',
      'test_people_copy' => 'test_people_copy',
    ];
    $this->assertEquals($expected, db_find_tables('test_people%'));
  }

  /**
   * Tests the db_set_active() function.
   *
   * @expectedDeprecation db_set_active() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Use \Drupal\Core\Database\Database::setActiveConnection() instead. See https://www.drupal.org/node/2993033
   */
  public function testDbSetActive() {
    $get_active_db = $this->connection->getKey();
    $this->assert(db_set_active($get_active_db), 'Database connection is active');
  }

  /**
   * Tests the db_drop_table() function.
   *
   * @expectedDeprecation db_drop_table() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Use \Drupal\Core\Database\Database::getConnection()->schema()->dropTable() instead. See https://www.drupal.org/node/2993033
   */
  public function testDbDropTable() {
    $this->assertFalse(db_drop_table('temp_test_table'));
  }

  /**
   * Tests deprecation of the db_next_id() function.
   *
   * @expectedDeprecation db_next_id() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call nextId() on it. For example, $injected_database->nextId($existing_id). See https://www.drupal.org/node/2993033
   */
  public function testDbNextId() {
    $this->installSchema('system', 'sequences');
    $this->assertEquals(1001, db_next_id(1000));
  }

  /**
   * Tests the db_change_field() function is deprecated.
   *
   * @expectedDeprecation db_change_field() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call changeField() on it. For example, $injected_database->schema()->changeField($table, $field, $field_new, $spec, $keys_new). See https://www.drupal.org/node/2993033
   * @doesNotPerformAssertions
   */
  public function testDbChangeField() {
    $spec = [
      'description' => "A new person's name",
      'type' => 'varchar_ascii',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
      'binary' => TRUE,
    ];
    db_change_field('test', 'name', 'nosuchcolumn', $spec);
  }

  /**
   * Tests deprecation of the db_field_set_default() function.
   *
   * @expectedDeprecation db_field_set_default() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call changeField() on it, passing a full field specification. For example, $injected_database->schema()->changeField($table, $field, $field_new, $spec, $keys_new). See https://www.drupal.org/node/2993033
   * @expectedDeprecation fieldSetDefault() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, call ::changeField() passing a full field specification. See https://www.drupal.org/node/2999035
   * @doesNotPerformAssertions
   */
  public function testDbFieldSetDefault() {
    db_field_set_default('test', 'job', 'baz');
  }

  /**
   * Tests deprecation of the db_field_set_no_default() function.
   *
   * @expectedDeprecation db_field_set_no_default() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call changeField() on it, passing a full field specification. For example, $injected_database->schema()->changeField($table, $field, $field_new, $spec, $keys_new). See https://www.drupal.org/node/2993033
   * @expectedDeprecation fieldSetNoDefault() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, call ::changeField() passing a full field specification. See https://www.drupal.org/node/2999035
   * @doesNotPerformAssertions
   */
  public function testDbFieldSetNoDefault() {
    db_field_set_no_default('test_null', 'age');
  }

  /**
   * Tests Schema::fieldSetDefault and Schema::fieldSetNoDefault.
   *
   * @expectedDeprecation fieldSetDefault() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, call ::changeField() passing a full field specification. See https://www.drupal.org/node/2999035
   * @expectedDeprecation fieldSetNoDefault() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, call ::changeField() passing a full field specification. See https://www.drupal.org/node/2999035
   */
  public function testSchemaFieldDefaultChange() {
    // Create a table.
    $table_specification = [
      'description' => 'Schema table description.',
      'fields' => [
        'id'  => [
          'type' => 'int',
          'default' => NULL,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
          'description' => 'Test field',
        ],
      ],
    ];
    $this->connection->schema()->createTable('test_table', $table_specification);

    // An insert without a value for the column 'test_field' should fail.
    try {
      $this->connection->insert('test_table')->fields(['id' => 1])->execute();
      $this->fail('Expected DatabaseException, none was thrown.');
    }
    catch (DatabaseException $e) {
      $this->assertEquals(0, $this->connection->select('test_table')->countQuery()->execute()->fetchField());
    }

    // Add a default value to the column.
    $this->connection->schema()->fieldSetDefault('test_table', 'test_field', 0);

    // The insert should now succeed.
    $this->connection->insert('test_table')->fields(['id' => 1])->execute();
    $this->assertEquals(1, $this->connection->select('test_table')->countQuery()->execute()->fetchField());

    // Remove the default.
    $this->connection->schema()->fieldSetNoDefault('test_table', 'test_field');

    // The insert should fail again.
    try {
      $this->connection->insert('test_table')->fields(['id' => 2])->execute();
      $this->fail('Expected DatabaseException, none was thrown.');
    }
    catch (DatabaseException $e) {
      $this->assertEquals(1, $this->connection->select('test_table')->countQuery()->execute()->fetchField());
    }
  }

  /**
   * Tests deprecation of the db_transaction() function.
   *
   * @expectedDeprecation db_transaction is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call startTransaction() on it. For example, $injected_database->startTransaction($name). See https://www.drupal.org/node/2993033
   */
  public function testDbTransaction() {
    $this->assertInstanceOf(Transaction::class, db_transaction());
  }

  /**
   * Tests the db_close() function.
   *
   * @expectedDeprecation db_close() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Use \Drupal\Core\Database\Database::closeConnection() instead. See https://www.drupal.org/node/2993033
   */
  public function testDbClose() {
    $this->assertTrue(Database::isActiveConnection(), 'Database connection is active');
    db_close();
    $this->assertFalse(Database::isActiveConnection(), 'Database connection is not active');
  }

  /**
   * Tests deprecation of the db_add_field() function.
   *
   * @expectedDeprecation db_add_field() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call addField() on it. For example, $injected_database->schema()->addField($table, $field, $spec, $keys_new). See https://www.drupal.org/node/2993033
   */
  public function testDbAddField() {
    $this->assertFalse($this->connection->schema()->fieldExists('test', 'anint'));
    db_add_field('test', 'anint', [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'Added int column.',
    ]);
    $this->assertTrue($this->connection->schema()->fieldExists('test', 'anint'));
  }

  /**
   * Tests deprecation of the db_drop_field() function.
   *
   * @expectedDeprecation db_drop_field() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call dropField() on it. For example, $injected_database->schema()->dropField($table, $field). See https://www.drupal.org/node/2993033
   */
  public function testDbDropField() {
    $this->assertTrue($this->connection->schema()->fieldExists('test', 'age'));
    $this->assertTrue(db_drop_field('test', 'age'));
    $this->assertFalse($this->connection->schema()->fieldExists('test', 'age'));
  }

  /**
   * Tests deprecation of the db_field_exists() function.
   *
   * @expectedDeprecation db_field_exists() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call fieldExists() on it. For example, $injected_database->schema()->fieldExists($table, $field). See https://www.drupal.org/node/2993033
   */
  public function testDbFieldExists() {
    $this->assertTrue(db_field_exists('test', 'age'));
  }

  /**
   * Tests deprecation of the db_field_names() function.
   *
   * @expectedDeprecation db_field_names() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call fieldNames() on it. For example, $injected_database->schema()->fieldNames($fields). See https://www.drupal.org/node/2993033
   */
  public function testDbFieldNames() {
    $this->assertSame(['test_field'], db_field_names(['test_field']));
  }

  /**
   * Tests deprecation of the db_create_table() function.
   *
   * @expectedDeprecation db_create_table() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call createTable() on it. For example, $injected_database->schema()->createTable($name, $table). See https://www.drupal.org/node/2993033
   */
  public function testDbCreateTable() {
    $name = 'test_create_table';
    $table = [
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ];
    db_create_table($name, $table);
    $this->assertTrue($this->connection->schema()->tableExists($name));
  }

  /**
   * Tests deprecation of the db_merge() function.
   *
   * @expectedDeprecation db_merge() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call merge() on it. For example, $injected_database->merge($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbMerge() {
    $this->assertInstanceOf(Merge::class, db_merge('test'));
  }

  /**
   * Tests deprecation of the db_driver() function.
   *
   * @expectedDeprecation db_driver() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call driver() on it. For example, $injected_database->driver($string). See https://www.drupal.org/node/2993033
   */
  public function testDbDriver() {
    $this->assertNotNull(db_driver());
  }

  /**
   * Tests deprecation of the db_escape_field() function.
   *
   * @expectedDeprecation db_escape_field() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call escapeField() on it. For example, $injected_database->escapeField($field). See https://www.drupal.org/node/2993033
   */
  public function testDbEscapeField() {
    $this->assertNotNull(db_escape_field('test'));
  }

  /**
   * Tests deprecation of the db_like() function.
   *
   * @expectedDeprecation db_like() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call escapeLike() on it. For example, $injected_database->escapeLike($string). See https://www.drupal.org/node/2993033
   */
  public function testDbLike() {
    $this->assertSame('test\%', db_like('test%'));
  }

  /**
   * Tests deprecation of the db_escape_table() function.
   *
   * @expectedDeprecation db_escape_table() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call escapeTable() on it. For example, $injected_database->escapeTable($table). See https://www.drupal.org/node/2993033
   */
  public function testDbEscapeTable() {
    $this->assertNotNull(db_escape_table('test'));
  }

  /**
   * Tests deprecation of the db_rename_table() function.
   *
   * @expectedDeprecation db_rename_table() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call renameTable() on it. For example, $injected_database->schema()->renameTable($table, $new_name). See https://www.drupal.org/node/2993033
   */
  public function testDbRenameTable() {
    $this->assertTrue($this->connection->schema()->tableExists('test'));
    db_rename_table('test', 'test_rename');
    $this->assertTrue($this->connection->schema()->tableExists('test_rename'));
  }

  /**
   * Tests deprecation of the db_drop_index() function.
   *
   * @expectedDeprecation db_drop_index() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call dropIndex() on it. For example, $injected_database->schema()->dropIndex($table, $name). See https://www.drupal.org/node/2993033
   */
  public function testDbDropIndex() {
    $this->assertFalse(db_drop_index('test', 'no_such_index'));
  }

  /**
   * Tests deprecation of the db_index_exists() function.
   *
   * @expectedDeprecation db_index_exists() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call indexExists() on it. For example, $injected_database->schema()->indexExists($table, $name). See https://www.drupal.org/node/2993033
   */
  public function testDbIndexExists() {
    $this->assertFalse(db_index_exists('test', 'no_such_index'));
  }

  /**
   * Tests deprecation of the db_drop_unique_key() function.
   *
   * @expectedDeprecation db_drop_unique_key() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call dropUniqueKey() on it. For example, $injected_database->schema()->dropUniqueKey($table, $name). See https://www.drupal.org/node/2993033
   */
  public function testDbDropUniqueKey() {
    $this->assertTrue(db_drop_unique_key('test', 'name'));
  }

  /**
   * Tests deprecation of the db_add_unique_key() function.
   *
   * @expectedDeprecation db_add_unique_key() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call addUniqueKey() on it. For example, $injected_database->schema()->addUniqueKey($table, $name, $fields). See https://www.drupal.org/node/2993033
   * @doesNotPerformAssertions
   */
  public function testDbAddUniqueKey() {
    db_add_unique_key('test', 'age', ['age']);
  }

  /**
   * Tests deprecation of the db_drop_primary_key() function.
   *
   * @expectedDeprecation db_drop_primary_key() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call dropPrimaryKey() on it. For example, $injected_database->schema()->dropPrimaryKey($table). See https://www.drupal.org/node/2993033
   */
  public function testDbDropPrimaryKey() {
    $this->assertTrue(db_drop_primary_key('test_people'));
  }

  /**
   * Tests deprecation of the db_add_primary_key() function.
   *
   * @expectedDeprecation db_add_primary_key() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call addPrimaryKey() on it. For example, $injected_database->schema()->addPrimaryKey($table, $fields). See https://www.drupal.org/node/2993033
   * @doesNotPerformAssertions
   */
  public function testDbAddPrimaryKey() {
    $this->connection->schema()->dropPrimaryKey('test_people');
    db_add_primary_key('test_people', ['job']);
  }

  /**
   * Tests the db_update() function.
   *
   * @expectedDeprecation db_update() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call call update() on it. For example, $injected_database->update($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbUpdate() {
    $this->assertInstanceOf(Update::class, db_update('test'));
  }

  /**
   * Tests the db_query() function.
   *
   * @expectedDeprecation db_query() is deprecated in drupal:8.0.0. It will be removed before drupal:9.0.0. Instead, get a database connection injected into your service from the container and call query() on it. For example, $injected_database->query($query, $args, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbQuery() {
    $this->assertInstanceOf(StatementInterface::class, db_query('SELECT name FROM {test} WHERE name = :name', [':name' => "John"]));
  }

  /**
   * Tests deprecation of the db_delete() function.
   *
   * @expectedDeprecation db_delete is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call delete() on it. For example, $injected_database->delete($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbDelete() {
    $this->assertInstanceOf(Delete::class, db_delete('test'));
  }

  /**
   * Tests deprecation of the db_truncate() function.
   *
   * @expectedDeprecation db_truncate() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call truncate() on it. For example, $injected_database->truncate($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbTruncate() {
    $this->assertInstanceOf(Truncate::class, db_truncate('test'));
  }

  /**
   * Tests deprecation of the $options 'target' key in Connection::query.
   *
   * @expectedDeprecation Passing a 'target' key to \Drupal\Core\Database\Connection::query $options argument is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, use \Drupal\Core\Database\Database::getConnection($target)->query(). See https://www.drupal.org/node/2993033.
   */
  public function testDbOptionsTarget() {
    $this->assertNotNull($this->connection->query('SELECT * FROM {test}', [], ['target' => 'bar']));
  }

  /**
   * Tests deprecation of the $options 'target' key in Select.
   *
   * @expectedDeprecation Passing a 'target' key to \Drupal\Core\Database\Connection::query $options argument is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, use \Drupal\Core\Database\Database::getConnection($target)->query(). See https://www.drupal.org/node/2993033.
   */
  public function testDbOptionsTargetInSelect() {
    $this->assertNotNull($this->connection->select('test', 't', ['target' => 'bar'])->fields('t')->execute());
  }

  /**
   * Tests deprecation of the db_query_temporary() function.
   *
   * @expectedDeprecation db_query_temporary() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call queryTemporary() on it. For example, $injected_database->queryTemporary($query, $args, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbQueryTemporary() {
    $expected = $this->connection->select('test')->countQuery()->execute()->fetchField();
    $name = db_query_temporary('SELECT name FROM {test}');
    $count = $this->connection->select($name)->countQuery()->execute()->fetchField();
    $this->assertSame($expected, $count);
  }

  /**
   * Tests deprecation of the db_query_range() function.
   *
   * @expectedDeprecation db_query_range() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call queryRange() on it. For example, $injected_database->queryRange($query, $from, $count, $args, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbQueryRange() {
    $count = count(db_query_range('SELECT name FROM {test}', 1, 3)->fetchAll());
    $this->assertSame(3, $count);
  }

  /**
   * Tests deprecation of the db_add_index() function.
   *
   * @expectedDeprecation db_add_index() is deprecated in Drupal 8.0.x and will be removed in Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call addIndex() on it. For example, $injected_database->schema()->addIndex($table, $name, $fields, $spec). See https://www.drupal.org/node/2993033
   */
  public function testDbAddIndex() {
    $table_specification = [
      'fields' => [
        'age' => [
          'description' => "The person's age",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];
    $this->assertNull(db_add_index('test', 'test', ['age'], $table_specification));
  }

  /**
   * Tests the db_insert() function.
   *
   * @expectedDeprecation db_insert() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call insert() on it. For example, $injected_database->insert($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbInsert() {
    $this->assertInstanceOf(Insert::class, db_insert('test'));
  }

  /**
   * Tests the db_select() function.
   *
   * @expectedDeprecation db_select() is deprecated in drupal:8.0.0. It will be removed from drupal:9.0.0. Instead, get a database connection injected into your service from the container and call select() on it. For example, $injected_database->db_select($table, $alias, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbSelect() {
    $this->assertInstanceOf(Select::class, db_select('test'));
  }

  /**
   * Tests the db_ignore_replica() function.
   *
   * @expectedDeprecation db_ignore_replica() is deprecated in drupal:8.7.0. It will be removed from drupal:9.0.0. Use \Drupal\Core\Database\ReplicaKillSwitch::trigger() instead. See https://www.drupal.org/node/2997500
   */
  public function testDbIgnoreReplica() {
    $connection = Database::getConnectionInfo('default');
    Database::addConnectionInfo('default', 'replica', $connection['default']);
    db_ignore_replica();
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $session = \Drupal::service('session');
    $this->assertTrue($session->has('ignore_replica_server'));
  }

  /**
   * Tests the _db_get_target() function.
   *
   * @expectedDeprecation _db_get_target() is deprecated in drupal:8.8.0. Will be removed before drupal:9.0.0. See https://www.drupal.org/node/2993033
   */
  public function testDbGetTarget() {
    $op1 = $op2 = ['target' => 'replica'];
    $this->assertEquals('replica', _db_get_target($op1));
    $this->assertEquals('default', _db_get_target($op2, FALSE));
    $this->assertEmpty($op1);
    $this->assertEmpty($op2);
  }

}
