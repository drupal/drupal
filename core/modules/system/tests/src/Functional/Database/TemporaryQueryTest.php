<?php

namespace Drupal\Tests\system\Functional\Database;

use Drupal\Core\Database\Database;

/**
 * Tests the temporary query functionality.
 *
 * @group Database
 */
class TemporaryQueryTest extends DatabaseTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['database_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Returns the number of rows of a table.
   */
  public function countTableRows($table_name) {
    return Database::getConnection()->select($table_name)->countQuery()->execute()->fetchField();
  }

  /**
   * Confirms that temporary tables work and are limited to one request.
   */
  public function testTemporaryQuery() {
    $connection = Database::getConnection();
    $this->drupalGet('database_test/db_query_temporary');
    $data = json_decode($this->getSession()->getPage()->getContent());
    if ($data) {
      $this->assertEquals($this->countTableRows('test'), $data->row_count, 'The temporary table contains the correct amount of rows.');
      $this->assertFalse($connection->schema()->tableExists($data->table_name), 'The temporary table is, indeed, temporary.');
    }
    else {
      $this->fail('The creation of the temporary table failed.');
    }

    // Now try to run two temporary queries in the same request.
    $table_name_test = $connection->queryTemporary('SELECT name FROM {test}', []);
    $table_name_task = $connection->queryTemporary('SELECT pid FROM {test_task}', []);

    $this->assertEquals($this->countTableRows('test'), $this->countTableRows($table_name_test), 'A temporary table was created successfully in this request.');
    $this->assertEquals($this->countTableRows('test_task'), $this->countTableRows($table_name_task), 'A second temporary table was created successfully in this request.');

    // Check that leading whitespace and comments do not cause problems
    // in the modified query.
    $sql = "
      -- Let's select some rows into a temporary table
      SELECT name FROM {test}
    ";
    $table_name_test = $connection->queryTemporary($sql, []);
    $this->assertEquals($this->countTableRows('test'), $this->countTableRows($table_name_test), 'Leading white space and comments do not interfere with temporary table creation.');
  }

}
