<?php

/**
 * @file
 * Contains \Drupal\datetime\Tests\Views\SortDateTimeTest.
 */

namespace Drupal\datetime\Tests\Views;

use Drupal\views\Views;

/**
 * Tests for core Drupal\datetime\Plugin\views\sort\Date handler.
 *
 * @group datetime
 */
class SortDateTimeTest extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_sort_datetime'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add some basic test nodes.
    $dates = [
      '2014-10-10T00:03:00',
      '2000-10-10T00:01:00',
      '2000-10-10T00:02:00',
      '2000-10-10T00:03:00',
    ];
    foreach ($dates as $date) {
      $this->nodes[] = $this->drupalCreateNode([
        'field_date' => [
          'value' => $date,
        ]
      ]);
    }
  }

  /**
   * Tests the datetime sort handler.
   */
  public function testDateTimeSort() {
    $field = static::$field_name . '_value';
    $view = Views::getView('test_sort_datetime');

    // Sort order is DESC.
    $view->initHandlers();
    $view->sort[$field]->options['granularity'] = 'minute';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[3]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[1]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Check ASC.
    $view->initHandlers();
    $field = static::$field_name . '_value';
    $view->sort[$field]->options['order'] = 'ASC';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[1]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[3]->id()],
      ['nid' => $this->nodes[0]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Change granularity to 'year', and the secondary node ID order should
    // define the order of nodes with the same year.
    $view->initHandlers();
    $view->sort[$field]->options['granularity'] = 'year';
    $view->sort[$field]->options['order'] = 'DESC';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[1]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();
  }

}
