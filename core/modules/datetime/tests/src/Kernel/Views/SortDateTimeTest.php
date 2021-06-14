<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\node\Entity\Node;
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
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Add some basic test nodes.
    $dates = [
      '2014-10-10T00:03:00',
      '2000-10-10T00:01:00',
      '2000-10-10T00:02:00',
      '2000-10-10T00:03:00',
      '2000-10-10T00:03:02',
      '2000-10-10T00:03:01',
      '2000-10-10T00:03:03',
    ];
    foreach ($dates as $date) {
      $node = Node::create([
        'title' => $this->randomMachineName(8),
        'type' => 'page',
        'field_date' => [
          'value' => $date,
        ],
      ]);
      $node->save();
      $this->nodes[] = $node;
    }
  }

  /**
   * Tests the datetime sort handler.
   */
  public function testDateTimeSort() {
    $field = static::$field_name . '_value';
    $view = Views::getView('test_sort_datetime');

    // Set granularity to 'minute', and the secondary node ID order should
    // define the order of nodes with the same minute.
    $view->initHandlers();
    $view->sort[$field]->options['granularity'] = 'minute';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[3]->id()],
      ['nid' => $this->nodes[4]->id()],
      ['nid' => $this->nodes[5]->id()],
      ['nid' => $this->nodes[6]->id()],
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
      ['nid' => $this->nodes[5]->id()],
      ['nid' => $this->nodes[4]->id()],
      ['nid' => $this->nodes[6]->id()],
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
      ['nid' => $this->nodes[4]->id()],
      ['nid' => $this->nodes[5]->id()],
      ['nid' => $this->nodes[6]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Change granularity to 'second'.
    $view->initHandlers();
    $view->sort[$field]->options['granularity'] = 'second';
    $view->sort[$field]->options['order'] = 'DESC';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[6]->id()],
      ['nid' => $this->nodes[4]->id()],
      ['nid' => $this->nodes[5]->id()],
      ['nid' => $this->nodes[3]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[1]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();
  }

}
