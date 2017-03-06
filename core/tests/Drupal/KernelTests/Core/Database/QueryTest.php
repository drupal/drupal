<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests Drupal's extended prepared statement syntax..
 *
 * @group Database
 */
class QueryTest extends DatabaseTestBase {

  /**
   * Tests that we can pass an array of values directly in the query.
   */
  public function testArraySubstitution() {
    $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', [':ages[]' => [25, 26, 27]])->fetchAll();
    $this->assertEqual(count($names), 3, 'Correct number of names returned');

    $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', [':ages[]' => [25]])->fetchAll();
    $this->assertEqual(count($names), 1, 'Correct number of names returned');
  }

  /**
   * Tests that we can not pass a scalar value when an array is expected.
   */
  public function testScalarSubstitution() {
    try {
      $names = db_query('SELECT name FROM {test} WHERE age IN ( :ages[] ) ORDER BY age', [':ages[]' => 25])->fetchAll();
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
    $condition = [
      "1 ;INSERT INTO {test} (name) VALUES ('test12345678'); -- " => '',
      '1' => '',
    ];
    try {
      db_query("SELECT * FROM {test} WHERE name = :name", [':name' => $condition])->fetchObject();
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
   * Tests SQL injection via condition operator.
   */
  public function testConditionOperatorArgumentsSQLInjection() {
    $injection = "IS NOT NULL) ;INSERT INTO {test} (name) VALUES ('test12345678'); -- ";

    // Convert errors to exceptions for testing purposes below.
    set_error_handler(function ($severity, $message, $filename, $lineno) {
      throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    });
    try {
      $result = db_select('test', 't')
        ->fields('t')
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via condition operator.');
    }
    catch (\ErrorException $e) {
      $this->pass('SQL injection attempt via condition arguments should result in a database exception.');
    }

    // Test that the insert query that was used in the SQL injection attempt did
    // not result in a row being inserted in the database.
    $result = db_select('test')
      ->condition('name', 'test12345678')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertFalse($result, 'SQL injection attempt did not result in a row being inserted in the database table.');

    // Attempt SQLi via union query with no unsafe characters.
    $this->enableModules(['user']);
    $this->installEntitySchema('user');
    db_insert('test')
      ->fields(['name' => '123456'])
      ->execute();
    $injection = "= 1 UNION ALL SELECT password FROM user WHERE uid =";

    try {
      $result = db_select('test', 't')
        ->fields('t', ['name', 'name'])
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via operator.');
    }
    catch (\ErrorException $e) {
      $this->pass('SQL injection attempt via condition arguments should result in a database exception.');
    }

    // Attempt SQLi via union query - uppercase tablename.
    db_insert('TEST_UPPERCASE')
      ->fields(['name' => 'secrets'])
      ->execute();
    $injection = "IS NOT NULL) UNION ALL SELECT name FROM {TEST_UPPERCASE} -- ";

    try {
      $result = db_select('test', 't')
        ->fields('t', ['name'])
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via operator.');
    }
    catch (\ErrorException $e) {
      $this->pass('SQL injection attempt via condition arguments should result in a database exception.');
    }
    restore_error_handler();
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

    $count = db_query('SELECT COUNT(*) >= :count FROM {test}', [
      ':count' => 3,
    ])->fetchField();
    $this->assertEqual((bool) $count, TRUE);
  }

}
