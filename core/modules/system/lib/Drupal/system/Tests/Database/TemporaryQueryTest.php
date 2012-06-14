<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\TemporaryQueryTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\simpletest\WebTestBase;

/**
 * Temporary query tests.
 */
class TemporaryQueryTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Temporary query test',
      'description' => 'Test the temporary query functionality.',
      'group' => 'Database',
    );
  }

  function setUp() {
    parent::setUp('database_test');
  }

  /**
   * Return the number of rows of a table.
   */
  function countTableRows($table_name) {
    return db_select($table_name)->countQuery()->execute()->fetchField();
  }

  /**
   * Confirm that temporary tables work and are limited to one request.
   */
  function testTemporaryQuery() {
    $this->drupalGet('database_test/db_query_temporary');
    $data = json_decode($this->drupalGetContent());
    if ($data) {
      $this->assertEqual($this->countTableRows("system"), $data->row_count, t('The temporary table contains the correct amount of rows.'));
      $this->assertFalse(db_table_exists($data->table_name), t('The temporary table is, indeed, temporary.'));
    }
    else {
      $this->fail(t("The creation of the temporary table failed."));
    }

    // Now try to run two db_query_temporary() in the same request.
    $table_name_system = db_query_temporary('SELECT status FROM {system}', array());
    $table_name_users = db_query_temporary('SELECT uid FROM {users}', array());

    $this->assertEqual($this->countTableRows($table_name_system), $this->countTableRows("system"), t('A temporary table was created successfully in this request.'));
    $this->assertEqual($this->countTableRows($table_name_users), $this->countTableRows("users"), t('A second temporary table was created successfully in this request.'));
  }
}
