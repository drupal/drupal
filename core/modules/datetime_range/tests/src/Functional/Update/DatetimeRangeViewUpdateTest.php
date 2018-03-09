<?php

namespace Drupal\Tests\datetime_range\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Test update of views with datetime_range filters.
 *
 * @see https://www.drupal.org/node/2786577
 * @see datetime_range_post_update_views_string_plugin_id()
 *
 * @group Update
 * @group legacy
 */
class DatetimeRangeViewUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/datetime_range-filter-values.php',
    ];
  }

  /**
   * Tests that datetime_range filter values are updated properly.
   */
  public function testViewsPostUpdateDateRangeFilterValues() {

    // Load our pre-update test view.
    $view = View::load('test_datetime_range_filter_values');
    $data = $view->toArray();

    // Check pre-update filter values.
    $filter1 = $data['display']['default']['display_options']['filters']['field_range_value'];
    $this->assertSame('string', $filter1['plugin_id']);

    // Check pre-update filter with operator going to be mapped.
    $filter2 = $data['display']['default']['display_options']['filters']['field_range_end_value'];
    $this->assertSame('string', $filter2['plugin_id']);
    $this->assertSame('', $filter2['value']);
    $this->assertSame('contains', $filter2['operator']);

    // Check pre-update sort values.
    $sort = $data['display']['default']['display_options']['sorts']['field_range_value'];
    $this->assertSame('standard', $sort['plugin_id']);

    $this->runUpdates();

    // Reload and initialize our test view.
    $view = View::load('test_datetime_range_filter_values');
    $data = $view->toArray();

    // Check filter values.
    $filter1 = $data['display']['default']['display_options']['filters']['field_range_value'];
    $this->assertSame('datetime', $filter1['plugin_id']);
    $this->assertSame('2017', $filter1['value']['value']);
    $this->assertSame('=', $filter1['operator']);

    // Check string to datetime operator/value mapping.
    $filter2 = $data['display']['default']['display_options']['filters']['field_range_end_value'];
    $this->assertSame('datetime', $filter2['plugin_id']);
    $this->assertSame('.*', $filter2['value']['value']);
    $this->assertSame('regular_expression', $filter2['operator']);

    // Check sort values.
    $sort = $data['display']['default']['display_options']['sorts']['field_range_value'];
    $this->assertSame('datetime', $sort['plugin_id']);
  }

}
