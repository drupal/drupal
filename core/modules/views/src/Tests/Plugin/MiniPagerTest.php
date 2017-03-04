<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests the mini pager plugin.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\pager\Mini
 */
class MiniPagerTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_mini_pager'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * Nodes used by the test.
   *
   * @var array
   */
  protected $nodes;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);
    // Create a bunch of test nodes.
    for ($i = 0; $i < 20; $i++) {
      $this->nodes[] = $this->drupalCreateNode();
    }
  }

  /**
   * Tests the rendering of mini pagers.
   */
  public function testMiniPagerRender() {
    $this->drupalGet('test_mini_pager');
    $this->assertText('›› test', 'The next link appears on the first page.');
    $this->assertText('Page 1', 'The current page info shows the first page.');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the first page.');
    $this->assertText($this->nodes[0]->label());
    $this->assertText($this->nodes[1]->label());
    $this->assertText($this->nodes[2]->label());

    $this->drupalGet('test_mini_pager', ['query' => ['page' => 1]]);
    $this->assertText('‹‹ test', 'The previous link appears.');
    $this->assertText('Page 2', 'The current page info shows the second page.');
    $this->assertText('›› test', 'The next link appears.');
    $this->assertText($this->nodes[3]->label());
    $this->assertText($this->nodes[4]->label());
    $this->assertText($this->nodes[5]->label());

    $this->drupalGet('test_mini_pager', ['query' => ['page' => 6]]);
    $this->assertNoText('›› test', 'The next link appears on the last page.');
    $this->assertText('Page 7', 'The current page info shows the last page.');
    $this->assertText('‹‹ test', 'The previous link does not appear on the last page.');
    $this->assertText($this->nodes[18]->label());
    $this->assertText($this->nodes[19]->label());

    // Test @total value in result summary
    $view = Views::getView('test_mini_pager');
    $view->setDisplay('page_4');
    $this->executeView($view);
    $this->assertIdentical($view->get_total_rows, TRUE, 'The query was set to calculate the total number of rows.');
    $this->assertEqual(count($this->nodes), $view->total_rows, 'The total row count is equal to the number of nodes.');

    $this->drupalGet('test_mini_pager_total', ['query' => ['page' => 1]]);
    $this->assertText('of ' . count($this->nodes), 'The first page shows the total row count.');
    $this->drupalGet('test_mini_pager_total', ['query' => ['page' => 6]]);
    $this->assertText('of ' . count($this->nodes), 'The last page shows the total row count.');

    // Test a mini pager with just one item per page.
    $this->drupalGet('test_mini_pager_one');
    $this->assertText('››');
    $this->assertText('Page 1');
    $this->assertText($this->nodes[0]->label());

    $this->drupalGet('test_mini_pager_one', ['query' => ['page' => 1]]);
    $this->assertText('‹‹');
    $this->assertText('Page 2');
    $this->assertText('››');
    $this->assertText($this->nodes[1]->label());

    $this->drupalGet('test_mini_pager_one', ['query' => ['page' => 19]]);
    $this->assertNoText('››');
    $this->assertText('Page 20');
    $this->assertText('‹‹');
    $this->assertText($this->nodes[19]->label());

    $this->drupalGet('test_mini_pager_all');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the page.');
    $this->assertNoText('Page 1', 'The current page info shows the only page.');
    $this->assertNoText('test ››', 'The next link does not appear on the page.');
    $result = $this->xpath('//div[contains(@class, "views-row")]');
    $this->assertEqual(count($result), count($this->nodes), 'All rows appear on the page.');

    // Remove all items beside 1, so there should be no links shown.
    for ($i = 0; $i < 19; $i++) {
      $this->nodes[$i]->delete();
    }

    $this->drupalGet('test_mini_pager');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the page.');
    $this->assertNoText('Page 1', 'The current page info shows the only page.');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the page.');
    $this->assertText($this->nodes[19]->label());

    $view = Views::getView('test_mini_pager');
    $this->executeView($view);
    $this->assertIdentical($view->get_total_rows, NULL, 'The query was not forced to calculate the total number of results.');
    $this->assertIdentical($view->total_rows, 1, 'The pager calculated the total number of rows.');

    // Remove the last node as well and ensure that no "Page 1" is shown.
    $this->nodes[19]->delete();
    $this->drupalGet('test_mini_pager');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the page.');
    $this->assertNoText('Page 1', 'The current page info shows the only page.');
    $this->assertNoText('‹‹ test', 'The previous link does not appear on the page.');
  }

}
