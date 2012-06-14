<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\RegressionTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Regression tests.
 */
class RegressionTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Regression tests',
      'description' => 'Regression tests cases for the database layer.',
      'group' => 'Database',
    );
  }

  function setUp() {
    parent::setUp(array('node'));
  }

  /**
   * Regression test for #310447.
   *
   * Tries to insert non-ascii UTF-8 data in a database column and checks
   * if its stored properly.
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
    $this->assertIdentical($name, $from_database, t("The database handles UTF-8 characters cleanly."));
  }

  /**
   * Test the db_table_exists() function.
   */
  function testDBTableExists() {
    $this->assertIdentical(TRUE, db_table_exists('node'), t('Returns true for existent table.'));
    $this->assertIdentical(FALSE, db_table_exists('nosuchtable'), t('Returns false for nonexistent table.'));
  }

  /**
   * Test the db_field_exists() function.
   */
  function testDBFieldExists() {
    $this->assertIdentical(TRUE, db_field_exists('node', 'nid'), t('Returns true for existent column.'));
    $this->assertIdentical(FALSE, db_field_exists('node', 'nosuchcolumn'), t('Returns false for nonexistent column.'));
  }

  /**
   * Test the db_index_exists() function.
   */
  function testDBIndexExists() {
    $this->assertIdentical(TRUE, db_index_exists('node', 'node_created'), t('Returns true for existent index.'));
    $this->assertIdentical(FALSE, db_index_exists('node', 'nosuchindex'), t('Returns false for nonexistent index.'));
  }
}
