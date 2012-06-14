<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\RangeQueryTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\simpletest\WebTestBase;

/**
 * Range query tests.
 */
class RangeQueryTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Range query test',
      'description' => 'Test the Range query functionality.',
      'group' => 'Database',
    );
  }

  function setUp() {
    parent::setUp('database_test');
  }

  /**
   * Confirm that range query work and return correct result.
   */
  function testRangeQuery() {
    // Test if return correct number of rows.
    $range_rows = db_query_range("SELECT name FROM {system} ORDER BY name", 2, 3)->fetchAll();
    $this->assertEqual(count($range_rows), 3, t('Range query work and return correct number of rows.'));

    // Test if return target data.
    $raw_rows = db_query('SELECT name FROM {system} ORDER BY name')->fetchAll();
    $raw_rows = array_slice($raw_rows, 2, 3);
    $this->assertEqual($range_rows, $raw_rows, t('Range query work and return target data.'));
  }
}
