<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\QueryTest.
 */

namespace Drupal\system\Tests\Database;

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
    $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', array(':ages[]' => array(25, 26, 27)))->fetchAll();
    $this->assertEqual(count($names), 3, 'Correct number of names returned');

    $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', array(':ages[]' => array(25)))->fetchAll();
    $this->assertEqual(count($names), 1, 'Correct number of names returned');
  }

  /**
   * Tests that we can not pass a scalar value when an array is expected.
   */
  function testScalarSubstitution() {
    try {
      $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', array(':ages[]' => 25))->fetchAll();
      $this->fail('Array placeholder with scalar argument should result in an exception.');
    }
    catch (\InvalidArgumentException $e) {
      $this->pass('Array placeholder with scalar argument should result in an exception.');
    }

  }

  /**
   * Tests SQL injection via database query array arguments.
   */
  public function testArrayArgumentsSQLInjection() {
    // Attempt SQL injection and verify that it does not work.
    $condition = array(
      "1 ;INSERT INTO {test} (name) VALUES ('test12345678'); -- " => '',
      '1' => '',
    );
    try {
      db_query("SELECT * FROM {test} WHERE name = :name", array(':name' => $condition))->fetchObject();
      $this->fail('SQL injection attempt via array arguments should result in a database exception.');
    }
    catch (\InvalidArgumentException $e) {
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

  /**
   * Tests numeric query parameter expansion in expressions.
   *
   * @see \Drupal\Core\Database\Driver\sqlite\Statement::getStatement()
   * @see http://bugs.php.net/bug.php?id=45259
   */
  public function testNumericExpressionSubstitution() {
    $count = db_query('SELECT COUNT(*) >= 3 FROM {test}')->fetchField();
    $this->assertEqual((bool) $count, TRUE);

    $count = db_query('SELECT COUNT(*) >= :count FROM {test}', array(
      ':count' => 3,
    ))->fetchField();
    $this->assertEqual((bool) $count, TRUE);

    // Test that numeric arguments expressed as strings also work properly.
    $count = db_query('SELECT COUNT(*) >= :count FROM {test}', array(
      ':count' => (string) 3,
    ))->fetchField();
    $this->assertEqual((bool) $count, TRUE);
  }

}
