<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\TemporaryQueryTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests the temporary query functionality.
 *
 * @group Database
 */
class TemporaryQueryTest extends DatabaseWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('database_test');

  /**
   * Returns the number of rows of a table.
   */
  function countTableRows($table_name) {
    return db_select($table_name)->countQuery()->execute()->fetchField();
  }

  /**
   * Confirms that temporary tables work and are limited to one request.
   */
  function testTemporaryQuery() {
    $this->drupalGet('database_test/db_query_temporary');
    $data = json_decode($this->getRawContent());
    if ($data) {
      $this->assertEqual($this->countTableRows('test'), $data->row_count, 'The temporary table contains the correct amount of rows.');
      $this->assertFalse(db_table_exists($data->table_name), 'The temporary table is, indeed, temporary.');
    }
    else {
      $this->fail('The creation of the temporary table failed.');
    }

    // Now try to run two db_query_temporary() in the same request.
    $table_name_test = db_query_temporary('SELECT name FROM {test}', array());
    $table_name_task = db_query_temporary('SELECT pid FROM {test_task}', array());

    $this->assertEqual($this->countTableRows($table_name_test), $this->countTableRows('test'), 'A temporary table was created successfully in this request.');
    $this->assertEqual($this->countTableRows($table_name_task), $this->countTableRows('test_task'), 'A second temporary table was created successfully in this request.');

    // Check that leading whitespace and comments do not cause problems
    // in the modified query.
    $sql = "
      -- Let's select some rows into a temporary table
      SELECT name FROM {test}
    ";
    $table_name_test = db_query_temporary($sql, array());
    $this->assertEqual($this->countTableRows($table_name_test), $this->countTableRows('test'), 'Leading white space and comments do not interfere with temporary table creation.');
  }
}
