<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\SortRandomTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests for core Drupal\views\Plugin\views\sort\Random handler.
 *
 * @group views
 */
class SortRandomTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Add more items to the test set, to make the order tests more robust.
   */
  protected function dataSet() {
    $data = parent::dataSet();
    for ($i = 0; $i < 50; $i++) {
      $data[] = array(
        'name' => 'name_' . $i,
        'age' => $i,
        'job' => 'job_' . $i,
        'created' => rand(0, time()),
        'status' => 1,
      );
    }
    return $data;
  }

  /**
   * Return a basic view with random ordering.
   */
  protected function getBasicRandomView() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Add a random ordering.
    $view->displayHandlers->get('default')->overrideOption('sorts', array(
      'random' => array(
        'id' => 'random',
        'field' => 'random',
        'table' => 'views',
      ),
    ));

    return $view;
  }

  /**
   * Tests random ordering of the result set.
   *
   * @see DatabaseSelectTestCase::testRandomOrder()
   */
  public function testRandomOrdering() {
    // Execute a basic view first.
    $view = Views::getView('test_view');
    $this->executeView($view);

    // Verify the result.
    $this->assertEqual(count($this->dataSet()), count($view->result), 'The number of returned rows match.');
    $this->assertIdenticalResultset($view, $this->dataSet(), array(
      'views_test_data_name' => 'name',
      'views_test_data_age' => 'age',
    ));

    // Execute a random view, we expect the result set to be different.
    $view_random = $this->getBasicRandomView();
    $this->executeView($view_random);
    $this->assertEqual(count($this->dataSet()), count($view_random->result), 'The number of returned rows match.');
    $this->assertNotIdenticalResultset($view_random, $view->result, array(
      'views_test_data_name' => 'views_test_data_name',
      'views_test_data_age' => 'views_test_data_name',
    ));

    // Execute a second random view, we expect the result set to be different again.
    $view_random_2 = $this->getBasicRandomView();
    $this->executeView($view_random_2);
    $this->assertEqual(count($this->dataSet()), count($view_random_2->result), 'The number of returned rows match.');
    $this->assertNotIdenticalResultset($view_random, $view->result, array(
      'views_test_data_name' => 'views_test_data_name',
      'views_test_data_age' => 'views_test_data_name',
    ));
  }

}
