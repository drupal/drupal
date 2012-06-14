<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectTableSortDefaultTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests the tablesort query extender
 */
class SelectTableSortDefaultTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Tablesort query tests',
      'description' => 'Test the tablesort query extender.',
      'group' => 'Database',
    );
  }

  /**
   * Confirm that a tablesort query returns the correct results.
   *
   * Note that we have to make an HTTP request to a test page handler
   * because the pager depends on GET parameters.
   */
  function testTableSortQuery() {
    $sorts = array(
      array('field' => t('Task ID'), 'sort' => 'desc', 'first' => 'perform at superbowl', 'last' => 'eat'),
      array('field' => t('Task ID'), 'sort' => 'asc', 'first' => 'eat', 'last' => 'perform at superbowl'),
      array('field' => t('Task'), 'sort' => 'asc', 'first' => 'code', 'last' => 'sleep'),
      array('field' => t('Task'), 'sort' => 'desc', 'first' => 'sleep', 'last' => 'code'),
      // more elements here

    );

    foreach ($sorts as $sort) {
      $this->drupalGet('database_test/tablesort/', array('query' => array('order' => $sort['field'], 'sort' => $sort['sort'])));
      $data = json_decode($this->drupalGetContent());

      $first = array_shift($data->tasks);
      $last = array_pop($data->tasks);

      $this->assertEqual($first->task, $sort['first'], t('Items appear in the correct order.'));
      $this->assertEqual($last->task, $sort['last'], t('Items appear in the correct order.'));
    }
  }

  /**
   * Confirm that if a tablesort's orderByHeader is called before another orderBy, that the header happens first.
   *
   */
  function testTableSortQueryFirst() {
    $sorts = array(
      array('field' => t('Task ID'), 'sort' => 'desc', 'first' => 'perform at superbowl', 'last' => 'eat'),
      array('field' => t('Task ID'), 'sort' => 'asc', 'first' => 'eat', 'last' => 'perform at superbowl'),
      array('field' => t('Task'), 'sort' => 'asc', 'first' => 'code', 'last' => 'sleep'),
      array('field' => t('Task'), 'sort' => 'desc', 'first' => 'sleep', 'last' => 'code'),
      // more elements here

    );

    foreach ($sorts as $sort) {
      $this->drupalGet('database_test/tablesort_first/', array('query' => array('order' => $sort['field'], 'sort' => $sort['sort'])));
      $data = json_decode($this->drupalGetContent());

      $first = array_shift($data->tasks);
      $last = array_pop($data->tasks);

      $this->assertEqual($first->task, $sort['first'], t('Items appear in the correct order sorting by @field @sort.', array('@field' => $sort['field'], '@sort' => $sort['sort'])));
      $this->assertEqual($last->task, $sort['last'], t('Items appear in the correct order sorting by @field @sort.', array('@field' => $sort['field'], '@sort' => $sort['sort'])));
    }
  }

  /**
   * Confirm that if a sort is not set in a tableselect form there is no error thrown when using the default.
   */
  function testTableSortDefaultSort() {
    $this->drupalGet('database_test/tablesort_default_sort');
    // Any PHP errors or notices thrown would trigger a simpletest exception, so
    // no additional assertions are needed.
  }
}
