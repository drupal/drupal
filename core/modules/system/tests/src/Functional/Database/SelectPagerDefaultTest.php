<?php

namespace Drupal\Tests\system\Functional\Database;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the pager query select extender.
 *
 * @group Database
 */
class SelectPagerDefaultTest extends DatabaseTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    $count = Database::getConnection()->select('test')->countQuery()->execute()->fetchField();

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

      $this->assertCount($correct_number, $data->names, new FormattableMarkup('Correct number of records returned by pager: @number', ['@number' => $correct_number]));
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
    $count = Database::getConnection()->select('test_task')->countQuery()->execute()->fetchField();

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

      $this->assertCount($correct_number, $data->names, new FormattableMarkup('Correct number of records returned by pager: @number', ['@number' => $correct_number]));
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
      ->extend(PagerSelectExtender::class);
    $query
      ->fields('t', ['age'])
      ->orderBy('age')
      ->limit(5);

    $outer_query = $connection->select($query);
    $outer_query->addField('subquery', 'age');
    $outer_query->orderBy('age');

    $ages = $outer_query
      ->execute()
      ->fetchCol();
    $this->assertEquals([25, 26, 27, 28], $ages, 'Inner pager query returned the correct ages.');
  }

  /**
   * Confirms that a paging query results with a having expression are valid.
   *
   * This is a regression test for #467984.
   */
  public function testHavingPagerQuery() {
    $query = Database::getConnection()->select('test', 't')
      ->extend(PagerSelectExtender::class);
    $query
      ->fields('t', ['name'])
      ->orderBy('name')
      ->groupBy('name')
      ->having('MAX([age]) > :count', [':count' => 26])
      ->limit(5);

    $ages = $query
      ->execute()
      ->fetchCol();
    $this->assertEquals(['George', 'Ringo'], $ages, 'Pager query with having expression returned the correct ages.');
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
    $query = $connection->select('test', 't')
      ->extend(PagerSelectExtender::class)
      ->element(2)
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1);
    $this->assertSame(2, $query->getElement());
    $name = $query->execute()
      ->fetchField();
    $this->assertEquals('Paul', $name, 'Pager query #1 with a specified element ID returned the correct results.');

    // Setting an element smaller than the previous one should not collide with
    // the existing pager.
    $query = $connection->select('test', 't')
      ->extend(PagerSelectExtender::class)
      ->element(1)
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1);
    $this->assertSame(1, $query->getElement());
    $name = $query->execute()
      ->fetchField();
    $this->assertEquals('George', $name, 'Pager query #2 with a specified element ID returned the correct results.');

    $query = $connection->select('test', 't')
      ->extend(PagerSelectExtender::class)
      ->fields('t', ['name'])
      ->orderBy('age')
      ->limit(1);
    $this->assertSame(3, $query->getElement());
    $name = $query->execute()
      ->fetchField();
    $this->assertEquals('John', $name, 'Pager query #3 with a generated element ID returned the correct results.');

  }

}
