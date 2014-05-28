<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\QueryTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests Drupal-specific SQL syntax tests.
 */
class QueryTest extends DatabaseTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Custom query syntax tests',
      'description' => 'Test Drupal\'s extended prepared statement syntax..',
      'group' => 'Database',
    );
  }

  /**
   * Tests that we can pass an array of values directly in the query.
   */
  function testArraySubstitution() {
    $names = db_query('SELECT name FROM {test} WHERE age IN (:ages) ORDER BY age', array(':ages' => array(25, 26, 27)))->fetchAll();

    $this->assertEqual(count($names), 3, 'Correct number of names returned');
  }
}
