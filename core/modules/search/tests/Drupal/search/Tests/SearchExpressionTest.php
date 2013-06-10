<?php

/**
 * @file
 * Contains \Drupal\search\Tests\SearchExpressionTest.
 */

namespace Drupal\search\Tests;

use Drupal\search\SearchExpression;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the search expression class.
 *
 * @see \Drupal\search\SearchExpression
 */
class SearchExpressionTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Search expression insert/extract',
      'description' => 'Tests the search expression class.',
      'group' => 'Search',
    );
  }


  /**
   * Provides data for the search expression tests.
   *
   * @return array
   *   An array of values passed to the test methods.
   */
  public function dataProvider() {
    $cases = array(
      // Normal case.
      array('foo', 'bar', 'foo:bar', 'bar'),
      // Empty value: shouldn't insert.
      array('foo', NULL, '', NULL),
      // Space as value: should insert but retrieve empty string.
      array('foo', ' ', 'foo:', ''),
      // Empty string as value: should insert but retrieve empty string.
      array('foo', '', 'foo:', ''),
      // String zero as value: should insert.
      array('foo', '0', 'foo:0', '0'),
      // Numeric zero as value: should insert.
      array('foo', 0, 'foo:0', '0'),
    );
    return $cases;
  }

  /**
   * Tests the search expression methods.
   *
   * @dataProvider dataProvider
   */
  public function testInsertExtract($case_0, $case_1, $case_2, $case_3) {
    $base_expression = 'mykeyword';
    // Build an array of option, value, what should be in the expression, what
    // should be retrieved from expression.

    $after_insert = new SearchExpression($base_expression);
    $after_insert->insert($case_0, $case_1);

    if (empty($case_2)) {
      $this->assertEquals($base_expression, $after_insert->getExpression(), 'Empty insert does change expression.');
    }
    else {
      $this->assertEquals($base_expression . ' ' . $case_2, $after_insert->getExpression(), 'Insert added incorrect expression.');
    }

    $retrieved = $after_insert->extract($case_0);

    if (!isset($case_3)) {
      $this->assertFalse(isset($retrieved), 'Empty retrieval results in unset value.');
    }
    else {
      $this->assertEquals($case_3, $retrieved, 'Value is retrieved.');
    }

    $after_clear = $after_insert->insert($case_0);
    $this->assertEquals($base_expression, $after_clear->getExpression(), 'After clearing, base expression is not restored.');

    $cleared = $after_clear->extract($case_0);
    $this->assertFalse(isset($cleared), 'After clearing, value could be retrieved.');
  }

}
