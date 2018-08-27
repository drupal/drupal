<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Database\Database;

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
   * @expectedDeprecation db_and() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying an AND conjunction: new Condition('AND'), instead. See https://www.drupal.org/node/2993033.
   */
  public function testDbAnd() {
    $this->assertInstanceOf(Condition::class, db_and());
  }

  /**
   * Tests deprecation of the db_condition() function.
   *
   * @expectedDeprecation db_condition() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying the desired conjunction: new Condition($conjunction), instead. See https://www.drupal.org/node/2993033.
   */
  public function testDbCondition() {
    $this->assertInstanceOf(Condition::class, db_condition('AND'));
  }

  /**
   * Tests deprecation of the db_or() function.
   *
   * @expectedDeprecation db_or() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying an OR conjunction: new Condition('OR'), instead. See https://www.drupal.org/node/2993033.
   */
  public function testDbOr() {
    $this->assertInstanceOf(Condition::class, db_or());
  }

  /**
   * Tests deprecation of the db_xor() function.
   *
   * @expectedDeprecation db_xor() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Create a \Drupal\Core\Database\Query\Condition object, specifying a XOR conjunction: new Condition('XOR'), instead. See https://www.drupal.org/node/2993033.
   */
  public function testDbXor() {
    $this->assertInstanceOf(Condition::class, db_xor());
  }

  /**
   * Tests the db_table_exists() function.
   *
   * @expectedDeprecation db_table_exists() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use $injected_database->schema()->tableExists($table) instead. See https://www.drupal.org/node/2947929.
   */
  public function testDbTableExists() {
    $this->assertTrue(db_table_exists('test'));
  }

  /**
   * Tests the db_set_active() function.
   *
   * @expectedDeprecation db_set_active() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Database\Database::setActiveConnection() instead. See https://www.drupal.org/node/2944084.
   */
  public function testDbSetActive() {
    $get_active_db = $this->connection->getKey();
    $this->assert(db_set_active($get_active_db), 'Database connection is active');
  }

  /**
   * Tests the db_drop_table() function.
   *
   * @expectedDeprecation db_drop_table() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Database\Database::getConnection()->schema()->dropTable() instead. See https://www.drupal.org/node/2987737
   */
  public function testDbDropTable() {
    $this->assertFalse(db_drop_table('temp_test_table'));
  }

  /**
   * Tests deprecation of the db_next_id() function.
   *
   * @expectedDeprecation db_next_id() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container and call nextId() on it. For example, $injected_database->nextId($existing_id). See https://www.drupal.org/node/2993033
   */
  public function testDbNextId() {
    $this->installSchema('system', 'sequences');
    $this->assertEquals(1001, db_next_id(1000));
  }

  /**
   * Tests the db_change_field() function is deprecated.
   *
   * @expectedDeprecation Deprecated as of Drupal 8.0.x, will be removed in Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call changeField() on it. For example, $injected_database->schema()->changeField($table, $field, $field_new, $spec, $keys_new). See https://www.drupal.org/node/2993033
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
   * Tests deprecation of the db_transaction() function.
   *
   * @expectedDeprecation db_transaction is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container and call startTransaction() on it. For example, $injected_database->startTransaction($name). See https://www.drupal.org/node/2993033
   */
  public function testDbTransaction() {
    $this->assertInstanceOf(Transaction::class, db_transaction());
  }

  /**
   * Tests the db_close() function.
   *
   * @expectedDeprecation db_close() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Database\Database::closeConnection() instead. See https://www.drupal.org/node/2993033.
   */
  public function testDbClose() {
    $this->assertTrue(Database::isActiveConnection(), 'Database connection is active');
    db_close();
    $this->assertFalse(Database::isActiveConnection(), 'Database connection is not active');
  }

  /**
   * Tests deprecation of the db_add_field() function.
   *
   * @expectedDeprecation db_add_field() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call addField() on it. For example, $injected_database->schema()->addField($table, $field, $spec, $keys_new). See https://www.drupal.org/node/2993033
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
   * @expectedDeprecation db_drop_field() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call dropField() on it. For example, $injected_database->schema()->dropField($table, $field). See https://www.drupal.org/node/2993033
   */
  public function testDbDropField() {
    $this->assertTrue($this->connection->schema()->fieldExists('test', 'age'));
    $this->assertTrue(db_drop_field('test', 'age'));
    $this->assertFalse($this->connection->schema()->fieldExists('test', 'age'));
  }

  /**
   * Tests deprecation of the db_field_names() function.
   *
   * @expectedDeprecation db_field_names() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call fieldNames() on it. For example, $injected_database->schema()->fieldNames($fields). See https://www.drupal.org/node/2993033
   */
  public function testDbFieldNames() {
    $this->assertSame(['test_field'], db_field_names(['test_field']));
  }

  /**
   * Tests deprecation of the db_create_table() function.
   *
   * @expectedDeprecation db_create_table() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container, get its schema driver, and call createTable() on it. For example, $injected_database->schema()->createTable($name, $table). See https://www.drupal.org/node/2993033
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
   * @expectedDeprecation db_merge() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container and call merge() on it. For example, $injected_database->merge($table, $options). See https://www.drupal.org/node/2993033
   */
  public function testDbMerge() {
    $num_records_before = (int) $this->connection->select('test_people')->countQuery()->execute()->fetchField();
    $result = db_merge('test_people')
      ->key('job', 'Presenter')
      ->fields([
        'age' => 31,
        'name' => 'Tiffany',
      ])
      ->execute();
    $num_records_after = (int) $this->connection->select('test_people')->countQuery()->execute()->fetchField();
    $this->assertSame($num_records_before + 1, $num_records_after, 'Merge inserted properly.');
  }

  /**
   * Tests the db_update() function.
   *
   * @expectedDeprecation db_update() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Instead, get a database connection injected into your service from the container and call call update() on it. For example, $injected_database->update($table, $options); See https://www.drupal.org/node/2993033
   */
  public function testDbUpdate() {
    $this->assertInstanceOf(Update::class, db_update('test'));
  }

}
