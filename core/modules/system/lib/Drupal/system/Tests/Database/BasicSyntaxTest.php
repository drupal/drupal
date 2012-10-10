<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\BasicSyntaxTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests how the current database driver interprets the SQL syntax.
 *
 * In order to ensure consistent SQL handling throughout Drupal
 * across multiple kinds of database systems, we test that the
 * database system interprets SQL syntax in an expected fashion.
 */
class BasicSyntaxTest extends DatabaseTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Basic SQL syntax tests',
      'description' => 'Test SQL syntax interpretation.',
      'group' => 'Database',
    );
  }

  /**
   * Tests string concatenation.
   */
  function testBasicConcat() {
    $result = db_query('SELECT CONCAT(:a1, CONCAT(:a2, CONCAT(:a3, CONCAT(:a4, :a5))))', array(
      ':a1' => 'This',
      ':a2' => ' ',
      ':a3' => 'is',
      ':a4' => ' a ',
      ':a5' => 'test.',
    ));
    $this->assertIdentical($result->fetchField(), 'This is a test.', 'Basic CONCAT works.');
  }

  /**
   * Tests string concatenation with field values.
   */
  function testFieldConcat() {
    $result = db_query('SELECT CONCAT(:a1, CONCAT(name, CONCAT(:a2, CONCAT(age, :a3)))) FROM {test} WHERE age = :age', array(
      ':a1' => 'The age of ',
      ':a2' => ' is ',
      ':a3' => '.',
      ':age' => 25,
    ));
    $this->assertIdentical($result->fetchField(), 'The age of John is 25.', 'Field CONCAT works.');
  }

  /**
   * Tests escaping of LIKE wildcards.
   */
  function testLikeEscape() {
    db_insert('test')
      ->fields(array(
        'name' => 'Ring_',
      ))
      ->execute();

    // Match both "Ringo" and "Ring_".
    $num_matches = db_select('test', 't')
      ->condition('name', 'Ring_', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertIdentical($num_matches, '2', 'Found 2 records.');
    // Match only "Ring_" using a LIKE expression with no wildcards.
    $num_matches = db_select('test', 't')
      ->condition('name', db_like('Ring_'), 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertIdentical($num_matches, '1', 'Found 1 record.');
  }

  /**
   * Tests a LIKE query containing a backslash.
   */
  function testLikeBackslash() {
    db_insert('test')
      ->fields(array('name'))
      ->values(array(
        'name' => 'abcde\f',
      ))
      ->values(array(
        'name' => 'abc%\_',
      ))
      ->execute();

    // Match both rows using a LIKE expression with two wildcards and a verbatim
    // backslash.
    $num_matches = db_select('test', 't')
      ->condition('name', 'abc%\\\\_', 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertIdentical($num_matches, '2', 'Found 2 records.');
    // Match only the former using a LIKE expression with no wildcards.
    $num_matches = db_select('test', 't')
      ->condition('name', db_like('abc%\_'), 'LIKE')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertIdentical($num_matches, '1', 'Found 1 record.');
  }
}
