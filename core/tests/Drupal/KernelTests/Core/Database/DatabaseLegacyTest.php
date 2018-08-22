<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Transaction;

use Drupal\Core\Database\Database;

/**
 * Deprecation tests cases for the database layer.
 *
 * @group legacy
 */
class DatabaseLegacyTest extends DatabaseTestBase {

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

}
