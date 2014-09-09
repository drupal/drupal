<?php

/**
 * @file
 * Contains \Drupal\views\Tests\QueryGroupByTest.
 */

namespace Drupal\views\Tests;

use Drupal\views\Views;

/**
 * Tests aggregate functionality of views, for example count.
 *
 * @group views
 */
class QueryGroupByTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_group_by_in_filters', 'test_aggregate_count', 'test_group_by_count');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'entity_test', 'system', 'field', 'user');

  /**
   * The storage for the test entity type.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  public $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');

    $this->storage = $this->container->get('entity.manager')->getStorage('entity_test');
  }


  /**
   * Tests aggregate count feature.
   */
  public function testAggregateCount() {
    $this->setupTestEntities();

    $view = Views::getView('test_aggregate_count');
    $this->executeView($view);

    $this->assertEqual(count($view->result), 2, 'Make sure the count of items is right.');

    $types = array();
    foreach ($view->result as $item) {
      // num_records is a alias for id.
      $types[$item->entity_test_name] = $item->num_records;
    }

    $this->assertEqual($types['name1'], 4, 'Groupby the name: name1 returned the expected amount of results.');
    $this->assertEqual($types['name2'], 3, 'Groupby the name: name2 returned the expected amount of results.');
  }

  /**
   * Provides a test helper which runs a view with some aggregation function.
   *
   * @param string $aggregation_function
   *   Which aggregation function should be used, for example sum or count.
   * @param array $values
   *   The expected views result.
   */
  public function groupByTestHelper($aggregation_function, $values) {
    $this->setupTestEntities();

    $view = Views::getView('test_group_by_count');
    $view->setDisplay();
    $view->displayHandlers->get('default')->options['fields']['id']['group_type'] = $aggregation_function;
    $this->executeView($view);

    $this->assertEqual(count($view->result), 2, 'Make sure the count of items is right.');
    // Group by name to identify the right count.
    $results = array();
    foreach ($view->result as $item) {
      $results[$item->entity_test_name] = $item->id;
    }
    $this->assertEqual($results['name1'], $values[0], format_string('Aggregation with @aggregation_function and groupby name: name1 returned the expected amount of results', array('@aggregation_function' => $aggregation_function)));
    $this->assertEqual($results['name2'], $values[1], format_string('Aggregation with @aggregation_function and groupby name: name2 returned the expected amount of results', array('@aggregation_function' => $aggregation_function)));
  }

  /**
   * Helper method that creates some test entities.
   */
  protected function setupTestEntities() {
    // Create 4 entities with name1 and 3 nodes with name2.
    $entity_1 = array(
      'name' => 'name1',
    );

    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();
    $this->storage->create($entity_1)->save();

    $entity_2 = array(
      'name' => 'name2',
    );
    $this->storage->create($entity_2)->save();
    $this->storage->create($entity_2)->save();
    $this->storage->create($entity_2)->save();
  }

  /**
   * Tests the count aggregation function.
   */
  public function testGroupByCount() {
    $this->groupByTestHelper('count', array(4, 3));
  }

  /**
   * Tests the sum aggregation function.
   */
  public function testGroupBySum() {
    $this->groupByTestHelper('sum', array(10, 18));
  }

  /**
   * Tests the average aggregation function.
   */
  public function testGroupByAverage() {
    $this->groupByTestHelper('avg', array(2.5, 6));
  }

  /**
   * Tests the min aggregation function.
   */
  public function testGroupByMin() {
    $this->groupByTestHelper('min', array(1, 5));
  }

  /**
   * Tests the max aggregation function.
   */
  public function testGroupByMax() {
    $this->groupByTestHelper('max', array(4, 7));
  }

  /**
   * Tests groupby with filters.
   */
  public function testGroupByCountOnlyFilters() {
    // Check if GROUP BY and HAVING are included when a view
    // Doesn't display SUM, COUNT, MAX... functions in SELECT statement

    for ($x = 0; $x < 10; $x++) {
      $this->storage->create(array('name' => 'name1'))->save();
    }

    $view = Views::getView('test_group_by_in_filters');
    $this->executeView($view);

    $this->assertTrue(strpos($view->build_info['query'], 'GROUP BY'), 'Make sure that GROUP BY is in the query');
    $this->assertTrue(strpos($view->build_info['query'], 'HAVING'), 'Make sure that HAVING is in the query');
  }

}
