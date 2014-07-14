<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectPagerDefaultTest.
 */

namespace Drupal\system\Tests\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the pager query select extender.
 *
 * @group Database
 */
class SelectPagerDefaultTest extends DatabaseWebTestBase {

  /**
   * Confirms that a pager query returns the correct results.
   *
   * Note that we have to make an HTTP request to a test page handler
   * because the pager depends on GET parameters.
   */
  function testEvenPagerQuery() {
    // To keep the test from being too brittle, we determine up front
    // what the page count should be dynamically, and pass the control
    // information forward to the actual query on the other side of the
    // HTTP request.
    $limit = 2;
    $count = db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    $correct_number = $limit;
    $num_pages = floor($count / $limit);

    // If there is no remainder from rounding, subtract 1 since we index from 0.
    if (!($num_pages * $limit < $count)) {
      $num_pages--;
    }

    for ($page = 0; $page <= $num_pages; ++$page) {
      $this->drupalGet('database_test/pager_query_even/' . $limit, array('query' => array('page' => $page)));
      $data = json_decode($this->drupalGetContent());

      if ($page == $num_pages) {
        $correct_number = $count - ($limit * $page);
      }

      $this->assertEqual(count($data->names), $correct_number, format_string('Correct number of records returned by pager: @number', array('@number' => $correct_number)));
    }
  }

  /**
   * Confirms that a pager query returns the correct results.
   *
   * Note that we have to make an HTTP request to a test page handler
   * because the pager depends on GET parameters.
   */
  function testOddPagerQuery() {
    // To keep the test from being too brittle, we determine up front
    // what the page count should be dynamically, and pass the control
    // information forward to the actual query on the other side of the
    // HTTP request.
    $limit = 2;
    $count = db_query('SELECT COUNT(*) FROM {test_task}')->fetchField();

    $correct_number = $limit;
    $num_pages = floor($count / $limit);

    // If there is no remainder from rounding, subtract 1 since we index from 0.
    if (!($num_pages * $limit < $count)) {
      $num_pages--;
    }

    for ($page = 0; $page <= $num_pages; ++$page) {
      $this->drupalGet('database_test/pager_query_odd/' . $limit, array('query' => array('page' => $page)));
      $data = json_decode($this->drupalGetContent());

      if ($page == $num_pages) {
        $correct_number = $count - ($limit * $page);
      }

      $this->assertEqual(count($data->names), $correct_number, format_string('Correct number of records returned by pager: @number', array('@number' => $correct_number)));
    }
  }

  /**
   * Confirms that a pager query results with an inner pager query are valid.
   *
   * This is a regression test for #467984.
   */
  function testInnerPagerQuery() {
    $query = db_select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query
      ->fields('t', array('age'))
      ->orderBy('age')
      ->limit(5);

    $outer_query = db_select($query);
    $outer_query->addField('subquery', 'age');

    $ages = $outer_query
      ->execute()
      ->fetchCol();
    $this->assertEqual($ages, array(25, 26, 27, 28), 'Inner pager query returned the correct ages.');
  }

  /**
   * Confirms that a paging query results with a having expression are valid.
   *
   * This is a regression test for #467984.
   */
  function testHavingPagerQuery() {
    $query = db_select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query
      ->fields('t', array('name'))
      ->orderBy('name')
      ->groupBy('name')
      ->having('MAX(age) > :count', array(':count' => 26))
      ->limit(5);

    $ages = $query
      ->execute()
      ->fetchCol();
    $this->assertEqual($ages, array('George', 'Ringo'), 'Pager query with having expression returned the correct ages.');
  }

  /**
   * Confirms that every pager gets a valid, non-overlaping element ID.
   */
  function testElementNumbers() {

    $request = Request::createFromGlobals();
    $request->query->replace(array(
      'page' => '3, 2, 1, 0',
    ));
    \Drupal::getContainer()->get('request_stack')->push($request);

    $name = db_select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->element(2)
      ->fields('t', array('name'))
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'Paul', 'Pager query #1 with a specified element ID returned the correct results.');

    // Setting an element smaller than the previous one
    // should not overwrite the pager $maxElement with a smaller value.
    $name = db_select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->element(1)
      ->fields('t', array('name'))
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'George', 'Pager query #2 with a specified element ID returned the correct results.');

    $name = db_select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t', array('name'))
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'John', 'Pager query #3 with a generated element ID returned the correct results.');

  }
}
