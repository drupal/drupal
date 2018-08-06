<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Regression tests cases for the database layer.
 *
 * @group Database
 */
class RegressionTest extends DatabaseTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'user'];

  /**
   * Ensures that non-ASCII UTF-8 data is stored in the database properly.
   */
  public function testRegression_310447() {
    // That's a 255 character UTF-8 string.
    $job = str_repeat("é", 255);
    db_insert('test')
      ->fields([
        'name' => $this->randomMachineName(),
        'age' => 20,
        'job' => $job,
      ])->execute();

    $from_database = db_query('SELECT job FROM {test} WHERE job = :job', [':job' => $job])->fetchField();
    $this->assertSame($job, $from_database, 'The database handles UTF-8 characters cleanly.');
  }

  /**
   * Tests the db_table_exists() function.
   *
   * @group legacy
   *
   * @expectedDeprecation db_table_exists() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use $injected_database->schema()->tableExists($table) instead. See https://www.drupal.org/node/2947929.
   */
  public function testDBTableExists() {
    $this->assertSame(TRUE, db_table_exists('test'), 'Returns true for existent table.');
    $this->assertSame(FALSE, db_table_exists('nosuchtable'), 'Returns false for nonexistent table.');
  }

  /**
   * Tests the db_field_exists() function.
   */
  public function testDBFieldExists() {
    $this->assertSame(TRUE, db_field_exists('test', 'name'), 'Returns true for existent column.');
    $this->assertSame(FALSE, db_field_exists('test', 'nosuchcolumn'), 'Returns false for nonexistent column.');
  }

  /**
   * Tests the db_index_exists() function.
   */
  public function testDBIndexExists() {
    $this->assertSame(TRUE, db_index_exists('test', 'ages'), 'Returns true for existent index.');
    $this->assertSame(FALSE, db_index_exists('test', 'nosuchindex'), 'Returns false for nonexistent index.');
  }

  /**
   * Tests the db_set_active() function.
   *
   * @group legacy
   *
   * @expectedDeprecation db_set_active() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Database\Database::setActiveConnection() instead. See https://www.drupal.org/node/2944084.
   */
  public function testDBIsActive() {
    $get_active_db = Database::getConnection()->getKey();
    $this->assert(db_set_active($get_active_db), 'Database connection is active');
  }

  /**
   * Tests the db_drop_table() function.
   *
   * @group legacy
   *
   * @expectedDeprecation db_drop_table() is deprecated in Drupal 8.0.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Database\Database::getConnection()->schema()->dropTable() instead. See https://www.drupal.org/node/2987737
   */
  public function testDbDropTable() {
    $this->assertFalse(db_drop_table('temp_test_table'));
  }

}
