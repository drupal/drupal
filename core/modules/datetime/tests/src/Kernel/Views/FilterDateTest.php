<?php

namespace Drupal\Tests\datetime\Kernel\Views;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * Tests date-only fields.
 *
 * @group datetime
 */
class FilterDateTest extends DateTimeHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_datetime'];

  /**
   * An array of timezone extremes to test.
   *
   * @var string[]
   */
  protected static $timezones = [
    // UTC-12, no DST.
    'Pacific/Kwajalein',
    // UTC-11, no DST.
    'Pacific/Midway',
    // UTC-7, no DST.
    'America/Phoenix',
    // UTC.
    'UTC',
    // UTC+5:30, no DST.
    'Asia/Kolkata',
    // UTC+12, no DST.
    'Pacific/Funafuti',
    // UTC+13, no DST.
    'Pacific/Tongatapu',
  ];

  /**
   * {@inheritdoc}
   *
   * Create nodes with relative dates of yesterday, today, and tomorrow.
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Change field storage to date-only.
    $storage = FieldStorageConfig::load('node.' . static::$field_name);
    $storage->setSetting('datetime_type', DateTimeItem::DATETIME_TYPE_DATE);
    $storage->save();

    // Retrieve tomorrow, today and yesterday dates just to create the nodes.
    $timestamp = $this->getUTCEquivalentOfUserNowAsTimestamp();
    $dates = $this->getRelativeDateValuesFromTimestamp($timestamp);

    // Clean the nodes on setUp.
    $this->nodes = [];
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

    // Add a node where the date field is empty.
    $node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_date' => [],
    ]);
    $node->save();
    $this->nodes[] = $node;
  }

  /**
   * Test offsets with date-only fields.
   */
  public function testDateOffsets() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $timestamp = $this->getUTCEquivalentOfUserNowAsTimestamp();
      $dates = $this->getRelativeDateValuesFromTimestamp($timestamp);
      $this->updateNodesDateFieldsValues($dates);

      // Test simple operations.
      $view->initHandlers();

      // A greater than or equal to 'now', should return the 'today' and the
      // 'tomorrow' node.
      $view->filter[$field]->operator = '>=';
      $view->filter[$field]->value['type'] = 'offset';
      $view->filter[$field]->value['value'] = 'now';
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[0]->id()],
        ['nid' => $this->nodes[1]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();

      // Only dates in the past.
      $view->initHandlers();
      $view->filter[$field]->operator = '<';
      $view->filter[$field]->value['type'] = 'offset';
      $view->filter[$field]->value['value'] = 'now';
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[2]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();

      // Test offset for between operator. Only 'tomorrow' node should appear.
      $view->initHandlers();
      $view->filter[$field]->operator = 'between';
      $view->filter[$field]->value['type'] = 'offset';
      $view->filter[$field]->value['max'] = '+2 days';
      $view->filter[$field]->value['min'] = '+1 day';
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[0]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();

      // Test the empty operator.
      $view->initHandlers();
      $view->filter[$field]->operator = 'empty';
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[3]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();

      // Test the not empty operator.
      $view->initHandlers();
      $view->filter[$field]->operator = 'not empty';
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[0]->id()],
        ['nid' => $this->nodes[1]->id()],
        ['nid' => $this->nodes[2]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();
    }
  }

  /**
   * Test date filter with date-only fields.
   */
  public function testDateIs() {
    $view = Views::getView('test_filter_datetime');
    $field = static::$field_name . '_value';

    foreach (static::$timezones as $timezone) {

      $this->setSiteTimezone($timezone);
      $timestamp = $this->getUTCEquivalentOfUserNowAsTimestamp();
      $dates = $this->getRelativeDateValuesFromTimestamp($timestamp);
      $this->updateNodesDateFieldsValues($dates);

      // Test simple operations.
      $view->initHandlers();

      // Filtering with nodes date-only values (format: Y-m-d) to test UTC
      // conversion does NOT change the day.
      $view->filter[$field]->operator = '=';
      $view->filter[$field]->value['type'] = 'date';
      $view->filter[$field]->value['value'] = $this->nodes[2]->field_date->first()->getValue()['value'];
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[2]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();

      // Test offset for between operator. Only 'today' and 'tomorrow' nodes
      // should appear.
      $view->initHandlers();
      $view->filter[$field]->operator = 'between';
      $view->filter[$field]->value['type'] = 'date';
      $view->filter[$field]->value['max'] = $this->nodes[0]->field_date->first()->getValue()['value'];
      $view->filter[$field]->value['min'] = $this->nodes[1]->field_date->first()->getValue()['value'];
      $view->setDisplay('default');
      $this->executeView($view);
      $expected_result = [
        ['nid' => $this->nodes[0]->id()],
        ['nid' => $this->nodes[1]->id()],
      ];
      $this->assertIdenticalResultset($view, $expected_result, $this->map);
      $view->destroy();
    }
  }

  /**
   * Updates tests nodes date fields values.
   *
   * @param array $dates
   *   An array of DATETIME_DATE_STORAGE_FORMAT date values.
   */
  protected function updateNodesDateFieldsValues(array $dates) {
    foreach ($dates as $index => $date) {
      $this->nodes[$index]->{static::$field_name}->value = $date;
      $this->nodes[$index]->save();
    }
  }

}
