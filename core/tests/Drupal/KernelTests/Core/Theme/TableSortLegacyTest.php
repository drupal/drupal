<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Utility\TableSort;
use Drupal\KernelTests\KernelTestBase;

/**
 * Deprecation tests cases for the tablesort.inc file functions.
 *
 * @package Drupal\KernelTests\Core\Theme
 *
 * @group legacy
 */
class TableSortLegacyTest extends KernelTestBase {

  /**
   * Tests deprecation of the tablesort_init() function.
   *
   * @expectedDeprecation tablesort_init() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getContextFromRequest() instead. See https://www.drupal.org/node/3009182
   */
  public function testInit() {
    $context = tablesort_init([]);
    $this->assertArrayHasKey('query', $context);
    $this->assertArrayHasKey('sort', $context);
  }

  /**
   * Tests deprecation of the tablesort_header() function.
   *
   * @expectedDeprecation tablesort_header() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::header() instead. See https://www.drupal.org/node/3009182
   */
  public function testHeader() {
    $cell_content = '';
    $cell_attributes = [];
    tablesort_header($cell_content, $cell_attributes, [], []);
    $this->assertEquals('', $cell_content);
  }

  /**
   * Tests deprecation of the tablesort_get_query_parameters() function.
   *
   * @expectedDeprecation tablesort_get_query_parameters() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getQueryParameters() instead. See https://www.drupal.org/node/3009182
   */
  public function testQueryParameters() {
    $parameters = tablesort_get_query_parameters();
    $this->assertArrayNotHasKey('sort', $parameters);
    $this->assertArrayNotHasKey('order', $parameters);
  }

  /**
   * Tests deprecation of the tablesort_get_order() function.
   *
   * @expectedDeprecation tablesort_get_order() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getOrder() instead. See https://www.drupal.org/node/3009182
   */
  public function testOrder() {
    $this->assertEquals(
      [
        'name' => NULL,
        'sql' => NULL,
      ],
      tablesort_get_order([])
    );
  }

  /**
   * Tests deprecation of the tablesort_get_sort() function.
   *
   * @expectedDeprecation tablesort_get_sort() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getSort() instead. See https://www.drupal.org/node/3009182
   */
  public function testSort() {
    $this->assertEquals(TableSort::ASC, tablesort_get_sort([]));
  }

}
