<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Date handler.
 *
 * @group views
 */
class FieldDateTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {
    $schema = parent::schemaDefinition();
    $schema['views_test_data']['fields']['destroyed'] = [
      'description' => "The destruction date of this record",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => FALSE,
      'default' => 0,
      'size' => 'big',
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['created']['field']['id'] = 'date';
    $data['views_test_data']['destroyed'] = [
      'title' => 'Destroyed',
      'help' => 'Date in future this will be destroyed.',
      'field' => ['id' => 'date'],
      'argument' => ['id' => 'date'],
      'filter' => ['id' => 'date'],
      'sort' => ['id' => 'date'],
    ];
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function dataSet() {
    $data_set = parent::dataSet();
    foreach ($data_set as $i => $data) {
      $data_set[$i]['destroyed'] = gmmktime(0, 0, 0, 1, 1, 2050);
    }
    return $data_set;
  }

  /**
   * Sets up functional test of the views date field.
   */
  public function testFieldDate(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'created' => [
        'id' => 'created',
        'table' => 'views_test_data',
        'field' => 'created',
        'relationship' => 'none',
        // ISO 8601 format, see https://www.php.net/manual/datetime.format.php
        'custom_date_format' => 'c',
      ],
      'destroyed' => [
        'id' => 'destroyed',
        'table' => 'views_test_data',
        'field' => 'destroyed',
        'relationship' => 'none',
        'custom_date_format' => 'c',
      ],
    ]);
    $time = gmmktime(0, 0, 0, 1, 1, 2000);

    $this->executeView($view);

    $timezones = [
      NULL,
      'UTC',
      'America/New_York',
    ];

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');

    // Check each date/time in various timezones.
    foreach ($timezones as $timezone) {
      $dates = [
        'short' => $date_formatter->format($time, 'short', '', $timezone),
        'medium' => $date_formatter->format($time, 'medium', '', $timezone),
        'long' => $date_formatter->format($time, 'long', '', $timezone),
        'custom' => $date_formatter->format($time, 'custom', 'c', $timezone),
        'fallback' => $date_formatter->format($time, 'fallback', '', $timezone),
        'html_date' => $date_formatter->format($time, 'html_date', '', $timezone),
        'html_datetime' => $date_formatter->format($time, 'html_datetime', '', $timezone),
        'html_month' => $date_formatter->format($time, 'html_month', '', $timezone),
        'html_time' => $date_formatter->format($time, 'html_time', '', $timezone),
        'html_week' => $date_formatter->format($time, 'html_week', '', $timezone),
        'html_year' => $date_formatter->format($time, 'html_year', '', $timezone),
        'html_yearless_date' => $date_formatter->format($time, 'html_yearless_date', '', $timezone),
      ];
      $this->assertRenderedDatesEqual($view, $dates, $timezone);
    }

    // Check times in the past.
    $time_since = $date_formatter->formatTimeDiffSince($time);
    $intervals = [
      'raw time ago' => $time_since,
      'time ago' => "$time_since ago",
      'raw time span' => $time_since,
      'inverse time span' => "-$time_since",
      'time span' => "$time_since ago",
    ];
    $this->assertRenderedDatesEqual($view, $intervals);

    // Check times in the future.
    $time = gmmktime(0, 0, 0, 1, 1, 2050);
    $formatted = $date_formatter->formatTimeDiffUntil($time);
    $intervals = [
      'raw time span' => "-$formatted",
      'time span' => "$formatted hence",
    ];
    $this->assertRenderedFutureDatesEqual($view, $intervals);
  }

  /**
   * Asserts properly formatted display against 'created' field in view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View to be tested.
   * @param array $map
   *   Data map.
   * @param string|null $timezone
   *   Optional timezone.
   *
   * @internal
   */
  protected function assertRenderedDatesEqual(ViewExecutable $view, array $map, ?string $timezone = NULL): void {
    foreach ($map as $date_format => $expected_result) {
      $view->field['created']->options['date_format'] = $date_format;
      if (isset($timezone)) {
        $message = "$date_format format for timezone $timezone matches.";
        $view->field['created']->options['timezone'] = $timezone;
      }
      else {
        $message = "$date_format format matches.";
      }
      $actual_result = (string) $view->field['created']->advancedRender($view->result[0]);
      $this->assertEquals($expected_result, strip_tags($actual_result), $message);
    }
  }

  /**
   * Asserts properly formatted display against 'destroyed' field in view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View to be tested.
   * @param array $map
   *   Data map.
   *
   * @internal
   */
  protected function assertRenderedFutureDatesEqual(ViewExecutable $view, array $map): void {
    foreach ($map as $format => $result) {
      $view->field['destroyed']->options['date_format'] = $format;
      $view_result = (string) $view->field['destroyed']->advancedRender($view->result[0]);
      $this->assertEquals($result, strip_tags($view_result), "$format format matches.");
    }
  }

}
