<?php

namespace Drupal\Tests\system\Functional\Database;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the pager query select extender.
 *
 * @group Database
 */
class SelectPagerDefaultTest extends DatabaseTestBase {

  /**
   * Confirms that a pager query returns the correct results.
   *
   * Note that we have to make an HTTP request to a test page handler
   * because the pager depends on GET parameters.
   */
  public function testEvenPagerQuery() {
    // To keep the test from being too brittle, we determine up front
    // what the page count should be dynamically, and pass the control
    // information forward to the actual query on the other side of the
    // HTTP request.
    $limit = 2;
    $count = Database::getConnection()->query('SELECT COUNT(*) FROM {test}')->fetchField();

    $correct_number = $limit;
    $num_pages = floor($count / $limit);

    // If there is no remainder from rounding, subtract 1 since we index from 0.
    if (!($num_pages * $limit < $count)) {
      $num_pages--;
    }

    for ($page = 0; $page <= $num_pages; ++$page) {
      $this->drupalGet('database_test/pager_query_even/' . $limit, ['query' => ['page' => $page]]);
      $data = json_decode($this->getSession()->getPage()->getContent());

      if ($page == $num_pages) {
        $correct_number = $count - ($limit * $page);
      }

      $this->assertCount($correct_number, $data->names, format_string('Correct number of records returned by pager: @number', ['@number' => $correct_number]));
    }
  }

  /**
   * Confirms that a pager query returns the correct results.
   *
   * Note that we have to make an HTTP request to a test page handler
   * because the pager depends on GET parameters.
   */
  public function testOddPagerQuery() {
    // To keep the test from being too brittle, we determine up front
    // what the page count should be dynamically, and pass the control
    // information forward to the actual query on the other side of the
    // HTTP request.
    $limit = 2;
    $count = Database::getConnection()->query('SELECT COUNT(*) FROM {test_task}')->fetchField();

    $correct_number = $limit;
    $num_pages = floor($count / $limit);

    // If there is no remainder from rounding, subtract 1 since we index from 0.
    if (!($num_pages * $limit < $count)) {
      $num_pages--;
    }

    for ($page = 0; $page <= $num_pages; ++$page) {
      $this->drupalGet('database_test/pager_query_odd/' . $limit, ['query' => ['page' => $page]]);
      $data = json_decode($this->getSession()->getPage()->getContent());

      if ($page == $num_pages) {
        $correct_number = $count - ($limit * $page);
      }

      $this->assertCount($correct_number, $data->names, format_string('Correct number of records returned by pager: @number', ['@number' => $correct_number]));
    }
  }

  /**
   * Confirms that a pager query results with an inner pager query are valid.
   *
   * This is a regression test for #467984.
   */
  public function testInnerPagerQuery() {
    $connection = Database::getConnection();
    $query = $connection->select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query
      ->fields('t', ['age'])
      ->orderBy('age')
      ->limit(5);

    $outer_query = $connection->select($query);
    $outer_query->addField('subquery', 'age');

    $ages = $outer_query
      ->execute()
      ->fetchCol();
    $this->assertEqual($ages, [25, 26, 27, 28], 'Inner pager query returned the correct ages.');
  }

  /**
   * Confirms that a paging query results with a having expression are valid.
   *
   * This is a regression test for #467984.
   */
  public function testHavingPagerQuery() {
    $query = Database::getConnection()->select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query
      ->fields('t', ['name'])
      ->orderBy('name')
      ->groupBy('name')
      ->having('MAX(age) > :count', [':count' => 26])
      ->limit(5);

    $ages = $query
      ->execute()
      ->fetchCol();
    $this->assertEqual($ages, ['George', 'Ringo'], 'Pager query with having expression returned the correct ages.');
  }

  /**
   * Confirms that every pager gets a valid, non-overlapping element ID.
   */
  public function testElementNumbers() {

    $request = Request::createFromGlobals();
    $request->query->replace([
      'page' => '3, 2, 1, 0',
    ]);
    \Drupal::getContainer()->get('request_stack')->push($request);

    $connection = Database::getConnection();
    $name = $connection->select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->element(2)
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'Paul', 'Pager query #1 with a specified element ID returned the correct results.');

    // Setting an element smaller than the previous one
    // should not overwrite the pager $maxElement with a smaller value.
    $name = $connection->select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->element(1)
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'George', 'Pager query #2 with a specified element ID returned the correct results.');

    $name = $connection->select('test', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1)
      ->execute()
      ->fetchField();
    $this->assertEqual($name, 'John', 'Pager query #3 with a generated element ID returned the correct results.');

  }

}
