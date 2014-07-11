<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectTableSortDefaultTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests the tablesort query extender.
 *
 * @group Database
 */
class SelectTableSortDefaultTest extends DatabaseWebTestBase {

  /**
   * Confirms that a tablesort query returns the correct results.
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

      $this->assertEqual($first->task, $sort['first'], 'Items appear in the correct order.');
      $this->assertEqual($last->task, $sort['last'], 'Items appear in the correct order.');
    }
  }

  /**
   * Confirms precedence of tablesorts headers.
   *
   * If a tablesort's orderByHeader is called before another orderBy, then its
   * header happens first.
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

      $this->assertEqual($first->task, $sort['first'], format_string('Items appear in the correct order sorting by @field @sort.', array('@field' => $sort['field'], '@sort' => $sort['sort'])));
      $this->assertEqual($last->task, $sort['last'], format_string('Items appear in the correct order sorting by @field @sort.', array('@field' => $sort['field'], '@sort' => $sort['sort'])));
    }
  }

  /**
   * Confirms that no error is thrown if no sort is set in a tableselect.
   */
  function testTableSortDefaultSort() {
    $this->drupalGet('database_test/tablesort_default_sort');

    // Verify that the table was displayed. Just the header is checked for
    // because if there were any fatal errors or exceptions in displaying the
    // sorted table, it would not print the table.
    $this->assertText(t('Username'));
  }
}
