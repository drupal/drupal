<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\FieldDateTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Date handler.
 *
 * @group views
 */
class FieldDateTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['created']['field']['id'] = 'date';
    return $data;
  }

  public function testFieldDate() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', array(
      'created' => array(
        'id' => 'created',
        'table' => 'views_test_data',
        'field' => 'created',
        'relationship' => 'none',
        // c is iso 8601 date format @see http://php.net/manual/en/function.date.php
        'custom_date_format' => 'c',
      ),
    ));
    $time = gmmktime(0, 0, 0, 1, 1, 2000);

    $this->executeView($view);

    $timezones = array(
      NULL,
      'UTC',
      'America/New_York',
    );
    foreach ($timezones as $timezone) {
      $dates = array(
        'short' => format_date($time, 'short', '', $timezone),
        'medium' => format_date($time, 'medium', '', $timezone),
        'long' => format_date($time, 'long', '', $timezone),
        'custom' => format_date($time, 'custom', 'c', $timezone),
      );
      $this->assertRenderedDatesEqual($view, $dates, $timezone);
    }

    $intervals = array(
      'raw time ago' => $this->container->get('date.formatter')->formatInterval(REQUEST_TIME - $time, 2),
      'time ago' => t('%time ago', array('%time' => $this->container->get('date.formatter')->formatInterval(REQUEST_TIME - $time, 2))),
      // TODO write tests for them
//       'raw time span' => $this->container->get('date.formatter')->formatInterval(REQUEST_TIME - $time, 2),
//       'time span' => t('%time hence', array('%time' => $this->container->get('date.formatter')->formatInterval(REQUEST_TIME - $time, 2))),
    );
    $this->assertRenderedDatesEqual($view, $intervals);
  }

  protected function assertRenderedDatesEqual($view, $map, $timezone = NULL) {
    foreach ($map as $date_format => $expected_result) {
      $view->field['created']->options['date_format'] = $date_format;
      $t_args = array(
        '%value' => $expected_result,
        '%format' => $date_format,
      );
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

}
