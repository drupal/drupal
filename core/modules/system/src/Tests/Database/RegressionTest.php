<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\RegressionTest.
 */

namespace Drupal\system\Tests\Database;

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
  public static $modules = array('node', 'user');

  /**
   * Ensures that non-ASCII UTF-8 data is stored in the database properly.
   */
  function testRegression_310447() {
    // That's a 255 character UTF-8 string.
    $name = str_repeat("é", 255);
    db_insert('test')
      ->fields(array(
        'name' => $name,
        'age' => 20,
        'job' => 'Dancer',
      ))->execute();

    $from_database = db_query('SELECT name FROM {test} WHERE name = :name', array(':name' => $name))->fetchField();
    $this->assertIdentical($name, $from_database, 'The database handles UTF-8 characters cleanly.');
  }

  /**
   * Tests the db_table_exists() function.
   */
  function testDBTableExists() {
    $this->assertIdentical(TRUE, db_table_exists('test'), 'Returns true for existent table.');
    $this->assertIdentical(FALSE, db_table_exists('nosuchtable'), 'Returns false for nonexistent table.');
  }

  /**
   * Tests the db_field_exists() function.
   */
  function testDBFieldExists() {
    $this->assertIdentical(TRUE, db_field_exists('test', 'name'), 'Returns true for existent column.');
    $this->assertIdentical(FALSE, db_field_exists('test', 'nosuchcolumn'), 'Returns false for nonexistent column.');
  }

  /**
   * Tests the db_index_exists() function.
   */
  function testDBIndexExists() {
    $this->assertIdentical(TRUE, db_index_exists('test', 'ages'), 'Returns true for existent index.');
    $this->assertIdentical(FALSE, db_index_exists('test', 'nosuchindex'), 'Returns false for nonexistent index.');
  }
}
