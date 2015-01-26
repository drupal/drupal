<?php

/**
 * @file
 * Contains \Drupal\options\Tests\Views\OptionsListFilterTest.
 */

namespace Drupal\options\Tests\Views;

use Drupal\views\Views;

/**
 * Tests options list filter for views.
 *
 * @see \Drupal\field\Plugin\views\filter\ListField.
 * @group views
 */
class OptionsListFilterTest extends OptionsTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_options_list_filter'];

  /**
   * Tests options list field filter.
   */
  public function testViewsTestOptionsListFilter() {
    $view = Views::getView('test_options_list_filter');
    $this->executeView($view);

    $resultset = [
      ['nid' => $this->nodes[0]->nid->value],
      ['nid' => $this->nodes[1]->nid->value],
    ];

    $column_map = ['nid' => 'nid'];
    $this->assertIdenticalResultset($view, $resultset, $column_map);
  }

}
