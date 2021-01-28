<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
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
    $datas = parent::dataSet();
    foreach ($datas as $i => $data) {
      $datas[$i]['destroyed'] = gmmktime(0, 0, 0, 1, 1, 2050);
    }
    return $datas;
  }

  /**
   * Sets up functional test of the views date field.
   */
  public function testFieldDate() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'created' => [
        'id' => 'created',
        'table' => 'views_test_data',
        'field' => 'created',
        'relationship' => 'none',
        // ISO 8601 format, see http://php.net/manual/function.date.php
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
      'time ago' => t('%time ago', ['%time' => $time_since]),
      'raw time span' => $time_since,
      'inverse time span' => "-$time_since",
      'time span' => t('%time ago', ['%time' => $time_since]),
    ];
    $this->assertRenderedDatesEqual($view, $intervals);

    // Check times in the future.
    $time = gmmktime(0, 0, 0, 1, 1, 2050);
    $formatted = $date_formatter->formatTimeDiffUntil($time);
    $intervals = [
      'raw time span' => "-$formatted",
      'time span' => t('%time hence', [
        '%time' => $formatted,
      ]),
    ];
    $this->assertRenderedFutureDatesEqual($view, $intervals);
  }

  /**
   * Asserts properly formatted display against 'created' field in view.
   *
   * @param mixed $view
   *   View to be tested.
   * @param array $map
   *   Data map.
   * @param null $timezone
   *   Optional timezone.
   */
  protected function assertRenderedDatesEqual($view, $map, $timezone = NULL) {
    foreach ($map as $date_format => $expected_result) {
      $view->field['created']->options['date_format'] = $date_format;
      $t_args = [
        '%value' => $expected_result,
        '%format' => $date_format,
      ];
      if (isset($timezone)) {
        $t_args['%timezone'] = $timezone;
        $message = t('Value %value in %format format for timezone %timezone matches.', $t_args);
        $view->field['created']->options['timezone'] = $timezone;
      }
      else {
        $message = t('Value %value in %format format matches.', $t_args);
      }
      $actual_result = $view->field['created']->advancedRender($view->result[0]);
      $this->assertEqual($expected_result, $actual_result, $message);
    }
  }

  /**
   * Asserts properly formatted display against 'destroyed' field in view.
   *
   * @param mixed $view
   *   View to be tested.
   * @param array $map
   *   Data map.
   */
  protected function assertRenderedFutureDatesEqual($view, $map) {
    foreach ($map as $format => $result) {
      $view->field['destroyed']->options['date_format'] = $format;
      $view_result = $view->field['destroyed']->advancedRender($view->result[0]);
      $t_args = [
        '%value' => $result,
        '%format' => $format,
        '%actual' => $view_result,
      ];
      $message = t('Value %value in %format matches %actual', $t_args);
      $this->assertEqual($result, $view_result, $message);
    }
  }

}
