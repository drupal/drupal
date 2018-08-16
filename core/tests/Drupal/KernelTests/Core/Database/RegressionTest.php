<?php

namespace Drupal\KernelTests\Core\Database;

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
   */
  public function testDBTableExists() {
    $this->assertSame(TRUE, $this->connection->schema()->tableExists('test'), 'Returns true for existent table.');
    $this->assertSame(FALSE, $this->connection->schema()->tableExists('nosuchtable'), 'Returns false for nonexistent table.');
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

}
