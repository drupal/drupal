<?php

namespace Drupal\KernelTests\Core\Database;

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

}
