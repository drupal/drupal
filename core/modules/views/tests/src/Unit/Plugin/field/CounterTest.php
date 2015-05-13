<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\Plugin\field\CounterTest.
 */

namespace Drupal\Tests\views\Unit\Plugin\field;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\field\Counter;
use Drupal\views\ResultRow;
use Drupal\views\Tests\ViewTestData;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\Counter
 * @group views
 */
class CounterTest extends UnitTestCase {

  /**
   * The pager plugin instance.
   *
   * @var \Drupal\views\Plugin\views\pager\PagerPluginBase
   */
  protected $pager;

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected  $view;

  /**
   * The display plugin instance.
   *
   * @var \Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  protected $display;


  /**
   * Stores the test data.
   *
   * @var array
   */
  protected $testData = array();

  /**
   * The handler definition of the counter field.
   *
   * @return array
   */
  protected $definition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup basic stuff like the view and the display.
    $config = array();
    $config['display']['default'] = array(
      'id' => 'default',
      'display_plugin' => 'default',
      'display_title' => 'Default',
    );

    $storage = new View($config, 'view');
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $this->view = $this->getMock('Drupal\views\ViewExecutable', NULL, array($storage, $user, $views_data, $route_provider));

    $this->display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $this->pager = $this->getMockBuilder('Drupal\views\Plugin\views\pager\Full')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->view->display_handler = $this->display;
    $this->view->pager = $this->pager;

    foreach (ViewTestData::dataSet() as $index => $set) {
      $this->testData[] = new ResultRow($set + ['index' => $index]);
    }

    $this->definition = array('title' => 'counter field', 'plugin_type' => 'field');
  }

  /**
   * Provides some row index to test.
   *
   * @return array
   *   Returns an array of row index to test.
   */
  public function providerRowIndexes() {
    return array(
      array(0),
      array(1),
      array(2),
    );
  }

  /**
   * Tests a simple counter field.
   *
   * @dataProvider providerRowIndexes
   */
  public function testSimpleCounter($i) {
    $counter_handler = new Counter(array(), 'counter', $this->definition);
    $options = array();
    $counter_handler->init($this->view, $this->display, $options);

    $this->view->row_index = $i;
    $expected = $i + 1;

    $counter = $counter_handler->getValue($this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
    $counter = $this->renderCounter($counter_handler, $this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
  }

  /**
   * Tests a counter with a random start.
   *
   * @param int $i
   *   The row index to test.
   *
   * @dataProvider providerRowIndexes
   */
  public function testCounterRandomStart($i) {
    // Setup a counter field with a random start.
    $rand_start = rand(5, 10);
    $counter_handler = new Counter(array(), 'counter', $this->definition);
    $options = array(
      'counter_start' => $rand_start,
    );
    $counter_handler->init($this->view, $this->display, $options);

    $this->view->row_index = $i;
    $expected = $rand_start + $i;

    $counter = $counter_handler->getValue($this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
    $counter = $this->renderCounter($counter_handler, $this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
  }

  /**
   * Tests a counter field with a random pager offset.
   *
   * @param int $i
   *   The row index to test.
   *
   * @dataProvider providerRowIndexes
   */
  public function testCounterRandomPagerOffset($i) {
    // Setup a counter field with a pager with a random offset.
    $offset = 3;
    $this->pager->setOffset($offset);

    $rand_start = rand(5, 10);
    $counter_handler = new Counter(array(), 'counter', $this->definition);
    $options = array(
      'counter_start' => $rand_start,
    );
    $counter_handler->init($this->view, $this->display, $options);

    $this->view->row_index = $i;
    $expected = $offset + $rand_start + $i;

    $counter = $counter_handler->getValue($this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
    $counter = $this->renderCounter($counter_handler, $this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
  }

  /**
   * Tests a counter field on the second page.
   *
   * @param int $i
   *   The row index to test.
   *
   * @dataProvider providerRowIndexes
   */
  public function testCounterSecondPage($i) {
    $offset = 3;
    // Setup a pager on the second page.
    $this->pager->setOffset($offset);
    $items_per_page = 5;
    $this->pager->setItemsPerPage($items_per_page);
    $current_page = 1;
    $this->pager->setCurrentPage($current_page);

    $rand_start = rand(5, 10);
    $counter_handler = new Counter(array(), 'counter', $this->definition);
    $options = array(
      'counter_start' => $rand_start,
    );
    $counter_handler->init($this->view, $this->display, $options);

    $this->view->row_index = $i;
    $expected = $items_per_page + $offset + $rand_start + $i;

    $counter = $counter_handler->getValue($this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
    $counter = $this->renderCounter($counter_handler, $this->testData[$i]);
    $this->assertEquals($expected, $counter, SafeMarkup::format('The expected number (@expected) patches with the rendered number (@counter) failed', array(
      '@expected' => $expected,
      '@counter' => $counter
    )));
  }

  /**
   * Renders the counter field handler.
   *
   * @param \Drupal\views\Plugin\views\field\Counter $handler
   *   The counter handler.
   * @param \Drupal\views\ResultRow $row
   *   A result row.
   *
   * @return string
   *   The counter rendered markup.
   */
  protected function renderCounter(Counter $handler, ResultRow $row) {
    $markup = $handler->render($row);
    $handler->postRender($row, $markup);
    return $handler->last_render;
  }

}
