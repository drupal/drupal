<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\SortDateTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Component\Utility\String;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests for core Drupal\views\Plugin\views\sort\Date handler.
 */
class SortDateTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  public static function getInfo() {
    return array(
      'name' => 'Sort: Date',
      'description' => 'Test the core Drupal\views\Plugin\views\sort\Date handler.',
      'group' => 'Views Handlers',
    );
  }

  protected function expectedResultSet($granularity, $reverse = TRUE) {
    $expected = array();
    if (!$reverse) {
      switch ($granularity) {
          case 'second':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'Paul'),
              array('name' => 'Meredith'),
              array('name' => 'Ringo'),
              array('name' => 'George'),
            );
            break;
          case 'minute':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'Paul'),
              array('name' => 'Ringo'),
              array('name' => 'Meredith'),
              array('name' => 'George'),
            );
            break;
          case 'hour':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'Ringo'),
              array('name' => 'Paul'),
              array('name' => 'Meredith'),
              array('name' => 'George'),
            );
            break;
          case 'day':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'Ringo'),
              array('name' => 'Paul'),
              array('name' => 'Meredith'),
              array('name' => 'George'),
            );
            break;
          case 'month':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'George'),
              array('name' => 'Ringo'),
              array('name' => 'Paul'),
              array('name' => 'Meredith'),
            );
            break;
          case 'year':
            $expected = array(
              array('name' => 'John'),
              array('name' => 'George'),
              array('name' => 'Ringo'),
              array('name' => 'Paul'),
              array('name' => 'Meredith'),
            );
            break;
        }
    }
    else {
      switch ($granularity) {
        case 'second':
          $expected = array(
            array('name' => 'George'),
            array('name' => 'Ringo'),
            array('name' => 'Meredith'),
            array('name' => 'Paul'),
            array('name' => 'John'),
          );
          break;
        case 'minute':
          $expected = array(
            array('name' => 'George'),
            array('name' => 'Ringo'),
            array('name' => 'Meredith'),
            array('name' => 'Paul'),
            array('name' => 'John'),
           );
          break;
        case 'hour':
          $expected = array(
            array('name' => 'George'),
            array('name' => 'Ringo'),
            array('name' => 'Paul'),
            array('name' => 'Meredith'),
            array('name' => 'John'),
          );
          break;
        case 'day':
          $expected = array(
            array('name' => 'George'),
            array('name' => 'John'),
            array('name' => 'Ringo'),
            array('name' => 'Paul'),
            array('name' => 'Meredith'),
          );
          break;
        case 'month':
          $expected = array(
            array('name' => 'John'),
            array('name' => 'George'),
            array('name' => 'Ringo'),
            array('name' => 'Paul'),
            array('name' => 'Meredith'),
          );
          break;
        case 'year':
          $expected = array(
            array('name' => 'John'),
            array('name' => 'George'),
            array('name' => 'Ringo'),
            array('name' => 'Paul'),
            array('name' => 'Meredith'),
          );
          break;
      }
    }

    return $expected;
  }

  /**
   * Tests numeric ordering of the result set.
   */
  public function testDateOrdering() {
    foreach (array('second', 'minute', 'hour', 'day', 'month', 'year') as $granularity) {
      foreach (array(FALSE, TRUE) as $reverse) {
        $view = Views::getView('test_view');
        $view->setDisplay();

        // Change the fields.
        $view->displayHandlers->get('default')->overrideOption('fields', array(
          'name' => array(
            'id' => 'name',
            'table' => 'views_test_data',
            'field' => 'name',
            'relationship' => 'none',
          ),
          'created' => array(
            'id' => 'created',
            'table' => 'views_test_data',
            'field' => 'created',
            'relationship' => 'none',
          ),
        ));

        // Change the ordering
        $view->displayHandlers->get('default')->overrideOption('sorts', array(
          'created' => array(
            'id' => 'created',
            'table' => 'views_test_data',
            'field' => 'created',
            'relationship' => 'none',
            'granularity' => $granularity,
            'order' => $reverse ? 'DESC' : 'ASC',
          ),
          'id' => array(
            'id' => 'id',
            'table' => 'views_test_data',
            'field' => 'id',
            'relationship' => 'none',
            'order' => 'ASC',
          ),
        ));

        // Execute the view.
        $this->executeView($view);

        // Verify the result.
        $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
        $this->assertIdenticalResultset($view, $this->expectedResultSet($granularity, $reverse), array(
          'views_test_data_name' => 'name',
        ), String::format('Result is returned correctly when ordering by granularity @granularity, @reverse.', array('@granularity' => $granularity, '@reverse' => $reverse ? 'reverse' : 'forward')));
        $view->destroy();
        unset($view);
      }
    }
  }

}
