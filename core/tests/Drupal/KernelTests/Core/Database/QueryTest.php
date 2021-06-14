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
    $names = $this->connection->query('SELECT [name] FROM {test} WHERE [age] IN ( :ages[] ) ORDER BY [age]', [':ages[]' => [25, 26, 27]])->fetchAll();
    $this->assertCount(3, $names, 'Correct number of names returned');

    $names = $this->connection->query('SELECT [name] FROM {test} WHERE [age] IN ( :ages[] ) ORDER BY [age]', [':ages[]' => [25]])->fetchAll();
    $this->assertCount(1, $names, 'Correct number of names returned');
  }

  /**
   * Tests that we can not pass a scalar value when an array is expected.
   */
  public function testScalarSubstitution() {
    try {
      $names = $this->connection->query('SELECT [name] FROM {test} WHERE [age] IN ( :ages[] ) ORDER BY [age]', [':ages[]' => 25])->fetchAll();
      $this->fail('Array placeholder with scalar argument should result in an exception.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(\InvalidArgumentException::class, $e);
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
      $this->connection->query("SELECT * FROM {test} WHERE [name] = :name", [':name' => $condition])->fetchObject();
      $this->fail('SQL injection attempt via array arguments should result in a database exception.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the insert query that was used in the SQL injection attempt did
    // not result in a row being inserted in the database.
    $result = $this->connection->select('test')
      ->condition('name', 'test12345678')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $result, 'SQL injection attempt did not result in a row being inserted in the database table.');
  }

  /**
   * Tests SQL injection via condition operator.
   */
  public function testConditionOperatorArgumentsSQLInjection() {
    $injection = "IS NOT NULL) ;INSERT INTO {test} (name) VALUES ('test12345678'); -- ";

    $previous_error_handler = set_error_handler(function ($severity, $message, $filename, $lineno) use (&$previous_error_handler) {
      // Normalize the filename to use UNIX directory separators.
      if (preg_match('@core/lib/Drupal/Core/Database/Query/Condition.php$@', str_replace(DIRECTORY_SEPARATOR, '/', $filename))) {
        // Convert errors to exceptions for testing purposes below.
        throw new \ErrorException($message, 0, $severity, $filename, $lineno);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $filename, $lineno);
      }
    });
    try {
      $result = $this->connection->select('test', 't')
        ->fields('t')
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via condition operator.');
    }
    catch (\ErrorException $e) {
      // Expected exception; just continue testing.
    }

    // Test that the insert query that was used in the SQL injection attempt did
    // not result in a row being inserted in the database.
    $result = $this->connection->select('test')
      ->condition('name', 'test12345678')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $result, 'SQL injection attempt did not result in a row being inserted in the database table.');

    // Attempt SQLi via union query with no unsafe characters.
    $this->enableModules(['user']);
    $this->installEntitySchema('user');
    $this->connection->insert('test')
      ->fields(['name' => '123456'])
      ->execute();
    $injection = "= 1 UNION ALL SELECT password FROM user WHERE uid =";

    try {
      $result = $this->connection->select('test', 't')
        ->fields('t', ['name', 'name'])
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via operator.');
    }
    catch (\ErrorException $e) {
      // Expected exception; just continue testing.
    }

    // Attempt SQLi via union query - uppercase tablename.
    $this->connection->insert('TEST_UPPERCASE')
      ->fields(['name' => 'secrets'])
      ->execute();
    $injection = "IS NOT NULL) UNION ALL SELECT name FROM {TEST_UPPERCASE} -- ";

    try {
      $result = $this->connection->select('test', 't')
        ->fields('t', ['name'])
        ->condition('name', 1, $injection)
        ->execute();
      $this->fail('Should not be able to attempt SQL injection via operator.');
    }
    catch (\ErrorException $e) {
      // Expected exception; just continue testing.
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
    $count_expected = $this->connection->query('SELECT COUNT(*) + 3 FROM {test}')->fetchField();

    $count = $this->connection->query('SELECT COUNT(*) + :count FROM {test}', [
      ':count' => 3,
    ])->fetchField();
    $this->assertEquals($count_expected, $count);
  }

  /**
   * Tests quoting identifiers in queries.
   */
  public function testQuotingIdentifiers() {
    // Use the table named an ANSI SQL reserved word with a column that is as
    // well.
    $result = $this->connection->query('SELECT [update] FROM {select}')->fetchObject();
    $this->assertEquals('Update value 1', $result->update);
  }

}
