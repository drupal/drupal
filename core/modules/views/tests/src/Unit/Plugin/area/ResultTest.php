<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\ViewsData;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\area\Result
 * @group views
 */
class ResultTest extends UnitTestCase {

  /**
   * The view executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The Result handler.
   *
   * @var \Drupal\views\Plugin\views\area\Result
   */
  protected $resultHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $storage = $this->prophesize(View::class);
    $storage->label()->willReturn('ResultTest');
    $storage->set(Argument::cetera())->willReturn(NULL);

    $user = $this->prophesize(AccountInterface::class)->reveal();
    $views_data = $this->prophesize(ViewsData::class)->reveal();
    $route_provider = $this->prophesize(RouteProviderInterface::class)->reveal();
    $this->view = new ViewExecutable($storage->reveal(), $user, $views_data, $route_provider);

    $this->resultHandler = new Result([], 'result', []);
    $this->resultHandler->view = $this->view;
  }

  /**
   * Tests the query method.
   */
  public function testQuery() {
    $this->assertNull($this->view->get_total_rows);
    // @total should set get_total_rows.
    $this->resultHandler->options['content'] = '@total';
    $this->resultHandler->query();
    $this->assertTrue($this->view->get_total_rows);
    // A different token should not.
    $this->view->get_total_rows = NULL;
    $this->resultHandler->options['content'] = '@current_page';
    $this->resultHandler->query();
    $this->assertNull($this->view->get_total_rows);
  }

  /**
   * Tests the rendered output of the Result area handler.
   *
   * @param string $content
   *   The content to use when rendering the handler.
   * @param string $expected
   *   The expected content string.
   * @param int $items_per_page
   *   The items per page of the configuration.
   *
   * @dataProvider providerTestResultArea
   */
  public function testResultArea($content, $expected, $items_per_page = 0) {
    $this->setupViewPager($items_per_page);
    $this->resultHandler->options['content'] = $content;
    $this->assertEquals(['#markup' => $expected], $this->resultHandler->render());
  }

  /**
   * Data provider for testResultArea.
   *
   * @return array
   */
  public function providerTestResultArea() {
    return [
      ['@label', 'ResultTest'],
      ['@start', '1'],
      ['@start', '1', 1],
      ['@end', '100'],
      ['@end', '1', 1],
      ['@total', '100'],
      ['@total', '100', 1],
      ['@per_page', '0'],
      ['@per_page', '1', 1],
      ['@current_page', '1'],
      ['@current_page', '1', 1],
      ['@current_record_count', '100'],
      ['@current_record_count', '1', 1],
      ['@page_count', '1'],
      ['@page_count', '100', 1],
      ['@start | @end | @total', '1 | 100 | 100'],
      ['@start | @end | @total', '1 | 1 | 100', 1],
    ];
  }

  /**
   * Sets up a mock pager on the view executable object.
   *
   * @param int $items_per_page
   *   The value to return from getItemsPerPage().
   */
  protected function setupViewPager($items_per_page = 0) {
    $pager = $this->prophesize(PagerPluginBase::class);
    $pager->getItemsPerPage()
      ->willReturn($items_per_page)
      ->shouldBeCalledTimes(1);
    $pager->getCurrentPage()
      ->willReturn(0)
      ->shouldBeCalledTimes(1);

    $this->view->pager = $pager->reveal();
    $this->view->style_plugin = new \stdClass();
    $this->view->total_rows = 100;
    $this->view->result = [1, 2, 3, 4, 5];
  }

}
