<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * Tests the Drupal\datetime\Plugin\views\filter\Date handler.
 *
 * @group datetime
 */
class FilterDateTimeTest extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_datetime'];

  /**
   * For offset tests, set a date 1 day in the future.
   *
   * @var int
   */
  protected static $date;

  /**
   * Use a non-UTC timezone.
   *
   * @var string
   */
  protected static $timezone = 'America/Vancouver';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    static::$date = REQUEST_TIME + 86400;

    // Set the timezone.
    date_default_timezone_set(static::$timezone);
    $this->config('system.date')
      ->set('timezone.default', static::$timezone)
      ->save();

    // Add some basic test nodes.
    $dates = [
      '2000-10-10T00:01:30',
      '2001-10-10T12:12:12',
      '2002-10-10T14:14:14',
      // The date storage timezone is used (this mimics the steps taken in the
      // widget: \Drupal\datetime\Plugin\Field\FieldWidget::messageFormValues().
      \Drupal::service('date.formatter')->format(static::$date, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE),
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
   * Tests filter operations.
   */
  public function testDatetimeFilter() {
    $this->_testOffset();
    $this->_testBetween();
    $this->_testExact();
  }

  /**
   * Tests offset operations.
   */
  protected function _testOffset() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    // Test simple operations.
    $view->initHandlers();

    $view->filter[$field]->operator = '>';
    $view->filter[$field]->value['type'] = 'offset';
    $view->filter[$field]->value['value'] = '+1 hour';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test offset for between operator.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['type'] = 'offset';
    $view->filter[$field]->value['max'] = '+2 days';
    $view->filter[$field]->value['min'] = '+1 hour';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

  /**
   * Tests between operations.
   */
  protected function _testBetween() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    // Test between with min and max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['min'] = '2001-01-01';
    $view->filter[$field]->value['max'] = '2002-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[1]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test between with just max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'between';
    $view->filter[$field]->value['max'] = '2002-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[1]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with min and max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'not between';
    $view->filter[$field]->value['min'] = '2001-01-01';
    // Set maximum date to date of node 1 to test range borders.
    $view->filter[$field]->value['max'] = '2001-10-10T12:12:12';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Test not between with just max.
    $view->initHandlers();
    $view->filter[$field]->operator = 'not between';
    $view->filter[$field]->value['max'] = '2001-01-01';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[1]->id()],
      ['nid' => $this->nodes[2]->id()],
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

  /**
   * Tests exact date matching.
   */
  protected function _testExact() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    // Test between with min and max.
    $view->initHandlers();
    $view->filter[$field]->operator = '=';
    $view->filter[$field]->value['min'] = '';
    $view->filter[$field]->value['max'] = '';
    // Use the date from node 3. Use the site timezone (mimics a value entered
    // through the UI).
    $view->filter[$field]->value['value'] = \Drupal::service('date.formatter')->format(static::$date, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, static::$timezone);
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

  }

}
