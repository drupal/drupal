<?php

/**
 * @file
 * Contains \Drupal\datetime\Tests\Views\FilterDateTimeTest.
 */

namespace Drupal\datetime\Tests\Views;

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
   */
  protected static $date;

  /**
   * Use a non-UTC timezone.
   */
  protected static $timezone = 'America/Vancouver';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    static::$date = REQUEST_TIME + 86400;

    // Set the timezone.
    date_default_timezone_set(static::$timezone);

    // Add some basic test nodes.
    $dates = [
      '2000-10-10T00:01:30',
      '2001-10-10T12:12:12',
      '2002-10-10T14:14:14',
      // The date storage timezone is used (this mimics the steps taken in the
      // widget: \Drupal\datetime\Plugin\Field\FieldWidget::messageFormValues().
      \Drupal::service('date.formatter')->format(static::$date, 'custom', DATETIME_DATETIME_STORAGE_FORMAT, DATETIME_STORAGE_TIMEZONE),
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
   * Test filter operations.
   */
  public function testDatetimeFilter() {
    $this->_testOffset();
    $this->_testBetween();
    $this->_testExact();
  }

  /**
   * Test offset operations.
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
   *  Test between operations.
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
    $view->filter[$field]->value['max'] = '2002-01-01';
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
   * Test exact date matching.
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
    $view->filter[$field]->value['value'] = \Drupal::service('date.formatter')->format(static::$date, 'custom', DATETIME_DATETIME_STORAGE_FORMAT, static::$timezone);
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[3]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

  }

}
