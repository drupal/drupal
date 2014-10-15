<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\QueryTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;

/**
 * Tests Drupal's extended prepared statement syntax..
 *
 * @group Database
 */
class QueryTest extends DatabaseTestBase {
  /**
   * Tests that we can pass an array of values directly in the query.
   */
  function testArraySubstitution() {
    $names = db_query('SELECT name FROM {test} WHERE age IN (:ages) ORDER BY age', array(':ages' => array(25, 26, 27)))->fetchAll();

    $this->assertEqual(count($names), 3, 'Correct number of names returned');
  }

  /**
   * Tests SQL injection via database query array arguments.
   */
  public function testArrayArgumentsSQLInjection() {
    // Attempt SQL injection and verify that it does not work.
    $condition = array(
      "1 ;INSERT INTO {test} SET name = 'test12345678'; -- " => '',
      '1' => '',
    );
    try {
      db_query("SELECT * FROM {test} WHERE name = :name", array(':name' => $condition))->fetchObject();
      $this->fail('SQL injection attempt via array arguments should result in a database exception.');
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->pass('SQL injection attempt via array arguments should result in a database exception.');
    }

    // Test that the insert query that was used in the SQL injection attempt did
    // not result in a row being inserted in the database.
    $result = db_select('test')
      ->condition('name', 'test12345678')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertFalse($result, 'SQL injection attempt did not result in a row being inserted in the database table.');
  }

}
