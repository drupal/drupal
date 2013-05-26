<?php

/**
 * @file
 * Definition of Drupal\views\Tests\QueryGroupByTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests aggregate functionality of views, for example count.
 */
class QueryGroupByTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_group_by_in_filters', 'test_aggregate_count', 'test_group_by_count');

  public static function getInfo() {
    return array(
      'name' => 'Groupby',
      'description' => 'Tests aggregate functionality of views, for example count.',
      'group' => 'Views',
    );
  }

  /**
   * Tests aggregate count feature.
   */
  public function testAggregateCount() {
    // Create 2 nodes of type1 and 3 nodes of type2
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();

    $node_1 = array(
      'type' => $type1->type,
    );
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);

    $node_2 = array(
      'type' => $type2->type,
    );
    $this->drupalCreateNode($node_2);
    $this->drupalCreateNode($node_2);
    $this->drupalCreateNode($node_2);

    $view = views_get_view('test_aggregate_count');
    $this->executeView($view);

    $this->assertEqual(count($view->result), 2, 'Make sure the count of items is right.');

    $types = array();
    foreach ($view->result as $item) {
      // num_records is a alias for nid.
      $types[$item->node_field_data_type] = $item->num_records;
    }

    $this->assertEqual($types[$type1->type], 4);
    $this->assertEqual($types[$type2->type], 3);
  }

  //public function testAggregateSum() {
  //}

  /**
   * @param $group_by
   *   Which group_by function should be used, for example sum or count.
   */
  function GroupByTestHelper($group_by, $values) {
    // Create 2 nodes of type1 and 3 nodes of type2
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();

    $node_1 = array(
      'type' => $type1->type,
    );
    // Nids from 1 to 4.
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);
    $this->drupalCreateNode($node_1);
    $node_2 = array(
      'type' => $type2->type,
    );
    // Nids from 5 to 7.
    $this->drupalCreateNode($node_2);
    $this->drupalCreateNode($node_2);
    $this->drupalCreateNode($node_2);

    $view = views_get_view('test_group_by_count');
    $view->setDisplay();
    $view->displayHandlers->get('default')->options['fields']['nid']['group_type'] = $group_by;
    $this->executeView($view);

    $this->assertEqual(count($view->result), 2, 'Make sure the count of items is right.');
    // Group by nodetype to identify the right count.
    foreach ($view->result as $item) {
      $results[$item->node_field_data_type] = $item->nid;
    }
    $this->assertEqual($results[$type1->type], $values[0]);
    $this->assertEqual($results[$type2->type], $values[1]);
  }

  public function testGroupByCount() {
    $this->GroupByTestHelper('count', array(4, 3));
  }

  function testGroupBySum() {
    $this->GroupByTestHelper('sum', array(10, 18));
  }

  function testGroupByAverage() {
    $this->GroupByTestHelper('avg', array(2.5, 6));
  }

  function testGroupByMin() {
    $this->GroupByTestHelper('min', array(1, 5));
  }

  function testGroupByMax() {
    $this->GroupByTestHelper('max', array(4, 7));
  }

  public function testGroupByCountOnlyFilters() {
    // Check if GROUP BY and HAVING are included when a view
    // Doesn't display SUM, COUNT, MAX... functions in SELECT statment

    $type1 = $this->drupalCreateContentType();

    $node_1 = array(
      'type' => $type1->type,
    );
    for ($x = 0; $x < 10; $x++) {
      $this->drupalCreateNode($node_1);
    }

    $view = views_get_view('test_group_by_in_filters');
    $this->executeView($view);

    $this->assertTrue(strpos($view->build_info['query'], 'GROUP BY'), 'Make sure that GROUP BY is in the query');
    $this->assertTrue(strpos($view->build_info['query'], 'HAVING'), 'Make sure that HAVING is in the query');
  }

}
