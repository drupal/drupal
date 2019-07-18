<?php

namespace Drupal\Tests\datetime_range\Kernel\Views;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\datetime\Kernel\Views\DateTimeHandlerTestBase;
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
  public static $modules = ['datetime_test', 'node', 'datetime_range', 'field'];

  /**
   * Type of the field.
   *
   * @var string
   */
  protected static $field_type = 'daterange';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_datetime'];

  /**
   * For offset tests, set to the current time.
   *
   * @var int
   */
  protected static $date;

  /**
   * {@inheritdoc}
   *
   * Create nodes with relative date range of:
   * yesterday - today, today - today, and today - tomorrow.
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Set to 'today'.
    static::$date = $this->getUTCEquivalentOfUserNowAsTimestamp();

    // Change field storage to date-only.
    $storage = FieldStorageConfig::load('node.' . static::$field_name);
    $storage->setSetting('datetime_type', DateRangeItem::DATETIME_TYPE_DATE);
    $storage->save();

    // Retrieve tomorrow, today and yesterday dates.
    $dates = $this->getRelativeDateValuesFromTimestamp(static::$date);

    // Node 0: Yesterday - Today.
    $node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_date' => [
        'value' => $dates[2],
        'end_value' => $dates[1],
      ],
    ]);
    $node->save();
    $this->nodes[] = $node;

    // Node 1: Today - Today.
    $node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_date' => [
        'value' => $dates[1],
        'end_value' => $dates[1],
      ],
    ]);
    $node->save();
    $this->nodes[] = $node;

    // Node 2: Today - Tomorrow.
    $node = Node::create([
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'field_date' => [
        'value' => $dates[1],
        'end_value' => $dates[0],
      ],
    ]);
    $node->save();
    $this->nodes[] = $node;

    // Add end date filter to the test_filter_datetime view.
    /** @var \Drupal\views\Entity\View $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('test_filter_datetime');
    $field_end = static::$field_name . '_end_value';
    $display = $view->getDisplay('default');
    $filter_end_date = $display['display_options']['filters'][static::$field_name . '_value'];
    $filter_end_date['id'] = $field_end;
    $filter_end_date['field'] = $field_end;

    $view->getDisplay('default')['display_options']['filters'][$field_end] = $filter_end_date;
    $view->save();
  }

  /**
   * Test offsets with date-only fields.
   */
  public function testDateOffsets() {
    $view = Views::getView('test_filter_datetime');
    $field_start = static::$field_name . '_value';
    $field_end = static::$field_name . '_end_value';

    // Test simple operations.
    $view->initHandlers();

    // Search nodes with:
    // - start date greater than or equal to 'yesterday'.
    // - end date lower than or equal to 'today'.
    // Expected results: nodes 0 and 1.
    $view->filter[$field_start]->operator = '>=';
    $view->filter[$field_start]->value['type'] = 'offset';
    $view->filter[$field_start]->value['value'] = '-1 day';
    $view->filter[$field_end]->operator = '<=';
    $view->filter[$field_end]->value['type'] = 'offset';
    $view->filter[$field_end]->value['value'] = 'now';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[0]->id()],
      ['nid' => $this->nodes[1]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
    $view->destroy();

    // Search nodes with:
    // - start date greater than or equal to 'yesterday'.
    // - end date greater than 'today'.
    // Expected results: node 2.
    $view->initHandlers();
    $view->filter[$field_start]->operator = '>=';
    $view->filter[$field_start]->value['type'] = 'offset';
    $view->filter[$field_start]->value['value'] = '-1 day';
    $view->filter[$field_end]->operator = '>';
    $view->filter[$field_end]->value['type'] = 'offset';
    $view->filter[$field_end]->value['value'] = 'now';
    $view->setDisplay('default');
    $this->executeView($view);
    $expected_result = [
      ['nid' => $this->nodes[2]->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->map);
  }

}
