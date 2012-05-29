<?php

/**
 * @file
 * Definition of Drupal\search\Tests\SearchExpressionInsertExtractTest.
 */

namespace Drupal\search\Tests;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests search_expression_insert() and search_expression_extract().
 *
 * @see http://drupal.org/node/419388 (issue)
 */
class SearchExpressionInsertExtractTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Search expression insert/extract',
      'description' => 'Tests the functions search_expression_insert() and search_expression_extract()',
      'group' => 'Search',
    );
  }

  function setUp() {
    drupal_load('module', 'search');
    parent::setUp();
  }

  /**
   * Tests search_expression_insert() and search_expression_extract().
   */
  function testInsertExtract() {
    $base_expression = "mykeyword";
    // Build an array of option, value, what should be in the expression, what
    // should be retrieved from expression.
    $cases = array(
      array('foo', 'bar', 'foo:bar', 'bar'), // Normal case.
      array('foo', NULL, '', NULL), // Empty value: shouldn't insert.
      array('foo', ' ', 'foo:', ''), // Space as value: should insert but retrieve empty string.
      array('foo', '', 'foo:', ''), // Empty string as value: should insert but retrieve empty string.
      array('foo', '0', 'foo:0', '0'), // String zero as value: should insert.
      array('foo', 0, 'foo:0', '0'), // Numeric zero as value: should insert.
    );

    foreach ($cases as $index => $case) {
      $after_insert = search_expression_insert($base_expression, $case[0], $case[1]);
      if (empty($case[2])) {
        $this->assertEqual($after_insert, $base_expression, "Empty insert does not change expression in case $index");
      }
      else {
        $this->assertEqual($after_insert, $base_expression . ' ' . $case[2], "Insert added correct expression for case $index");
      }

      $retrieved = search_expression_extract($after_insert, $case[0]);
      if (!isset($case[3])) {
        $this->assertFalse(isset($retrieved), "Empty retrieval results in unset value in case $index");
      }
      else {
        $this->assertEqual($retrieved, $case[3], "Value is retrieved for case $index");
      }

      $after_clear = search_expression_insert($after_insert, $case[0]);
      $this->assertEqual(trim($after_clear), $base_expression, "After clearing, base expression is restored for case $index");

      $cleared = search_expression_extract($after_clear, $case[0]);
      $this->assertFalse(isset($cleared), "After clearing, value could not be retrieved for case $index");
    }
  }
}
