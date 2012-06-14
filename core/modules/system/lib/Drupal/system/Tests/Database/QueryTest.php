<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\QueryTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Drupal-specific SQL syntax tests.
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
   * Test that we can specify an array of values in the query by simply passing in an array.
   */
  function testArraySubstitution() {
    $names = db_query('SELECT name FROM {test} WHERE age IN (:ages) ORDER BY age', array(':ages' => array(25, 26, 27)))->fetchAll();

    $this->assertEqual(count($names), 3, t('Correct number of names returned'));
  }
}
