<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\area\ResultTest.
 */

namespace Drupal\views\Tests\Plugin\area;

use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\area\Result;

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

  public function setUp() {
    parent::setUp();

    $storage = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->setMethods(array('label'))
      ->getMock();
    $storage->expects($this->any())
      ->method('label')
      ->will($this->returnValue('ResultTest'));

    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->view = new ViewExecutable($storage, $user);

    $this->resultHandler = new Result(array(), 'result', array());
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
    $this->assertEquals(array('#markup' => $expected), $this->resultHandler->render());
  }

  /**
   * Data provider for testResultArea.
   *
   * @return array
   */
  public function providerTestResultArea() {
    return array(
      array('@label', 'ResultTest'),
      array('@start', '1'),
      array('@start', '1', 1),
      array('@end', '100'),
      array('@end', '1', 1),
      array('@total', '100'),
      array('@total', '100', 1),
      array('@per_page', '0'),
      array('@per_page', '1', 1),
      array('@current_page', '1'),
      array('@current_page', '1', 1),
      array('@current_record_count', '100'),
      array('@current_record_count', '1', 1),
      array('@page_count', '1'),
      array('@page_count', '100', 1),
      array('@start | @end | @total', '1 | 100 | 100'),
      array('@start | @end | @total', '1 | 1 | 100', 1),
    );
  }

  /**
   * Sets up a mock pager on the view executable object.
   *
   * @param int $items_per_page
   *   The value to return from getItemsPerPage().
   */
  protected function setupViewPager($items_per_page = 0) {
    $pager = $this->getMockBuilder('Drupal\views\Plugin\views\pager\PagerPluginBase')
      ->disableOriginalConstructor()
      ->setMethods(array('getItemsPerPage', 'getCurrentPage'))
      ->getMock();
    $pager->expects($this->once())
      ->method('getItemsPerPage')
      ->will($this->returnValue($items_per_page));
    $pager->expects($this->once())
      ->method('getCurrentPage')
      ->will($this->returnValue(0));

    $this->view->pager = $pager;
    $this->view->style_plugin = new \stdClass();
    $this->view->total_rows = 100;
    $this->view->result = array(1,2,3,4,5);
  }

}
