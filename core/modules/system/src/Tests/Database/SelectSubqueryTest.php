<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectSubqueryTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests for subselects in a dynamic SELECT query.
 */
class SelectSubqueryTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Select tests, subqueries',
      'description' => 'Test the Select query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Tests that we can use a subquery in a FROM clause.
   */
  function testFromSubquerySelect() {
    // Create a subquery, which is just a normal query object.
    $subquery = db_select('test_task', 'tt');
    $subquery->addField('tt', 'pid', 'pid');
    $subquery->addField('tt', 'task', 'task');
    $subquery->condition('priority', 1);

    for ($i = 0; $i < 2; $i++) {
      // Create another query that joins against the virtual table resulting
      // from the subquery.
      $select = db_select($subquery, 'tt2');
      $select->join('test', 't', 't.id=tt2.pid');
      $select->addField('t', 'name');
      if ($i) {
        // Use a different number of conditions here to confuse the subquery
        // placeholder counter, testing http://drupal.org/node/1112854.
        $select->condition('name', 'John');
      }
      $select->condition('task', 'code');

      // The resulting query should be equivalent to:
      // SELECT t.name
      // FROM (SELECT tt.pid AS pid, tt.task AS task FROM test_task tt WHERE priority=1) tt
      //   INNER JOIN test t ON t.id=tt.pid
      // WHERE tt.task = 'code'
      $people = $select->execute()->fetchCol();

      $this->assertEqual(count($people), 1, 'Returned the correct number of rows.');
    }
  }

  /**
   * Tests that we can use a subquery in a FROM clause with a LIMIT.
   */
  function testFromSubquerySelectWithLimit() {
    // Create a subquery, which is just a normal query object.
    $subquery = db_select('test_task', 'tt');
    $subquery->addField('tt', 'pid', 'pid');
    $subquery->addField('tt', 'task', 'task');
    $subquery->orderBy('priority', 'DESC');
    $subquery->range(0, 1);

    // Create another query that joins against the virtual table resulting
    // from the subquery.
    $select = db_select($subquery, 'tt2');
    $select->join('test', 't', 't.id=tt2.pid');
    $select->addField('t', 'name');

    // The resulting query should be equivalent to:
    // SELECT t.name
    // FROM (SELECT tt.pid AS pid, tt.task AS task FROM test_task tt ORDER BY priority DESC LIMIT 1 OFFSET 0) tt
    //   INNER JOIN test t ON t.id=tt.pid
    $people = $select->execute()->fetchCol();

    $this->assertEqual(count($people), 1, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can use a subquery in a WHERE clause.
   */
  function testConditionSubquerySelect() {
    // Create a subquery, which is just a normal query object.
    $subquery = db_select('test_task', 'tt');
    $subquery->addField('tt', 'pid', 'pid');
    $subquery->condition('tt.priority', 1);

    // Create another query that joins against the virtual table resulting
    // from the subquery.
    $select = db_select('test_task', 'tt2');
    $select->addField('tt2', 'task');
    $select->condition('tt2.pid', $subquery, 'IN');

    // The resulting query should be equivalent to:
    // SELECT tt2.name
    // FROM test tt2
    // WHERE tt2.pid IN (SELECT tt.pid AS pid FROM test_task tt WHERE tt.priority=1)
    $people = $select->execute()->fetchCol();
    $this->assertEqual(count($people), 5, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can use a subquery in a JOIN clause.
   */
  function testJoinSubquerySelect() {
    // Create a subquery, which is just a normal query object.
    $subquery = db_select('test_task', 'tt');
    $subquery->addField('tt', 'pid', 'pid');
    $subquery->condition('priority', 1);

    // Create another query that joins against the virtual table resulting
    // from the subquery.
    $select = db_select('test', 't');
    $select->join($subquery, 'tt', 't.id=tt.pid');
    $select->addField('t', 'name');

    // The resulting query should be equivalent to:
    // SELECT t.name
    // FROM test t
    //   INNER JOIN (SELECT tt.pid AS pid FROM test_task tt WHERE priority=1) tt ON t.id=tt.pid
    $people = $select->execute()->fetchCol();

    $this->assertEqual(count($people), 2, 'Returned the correct number of rows.');
  }

  /**
   * Tests EXISTS subquery conditionals on SELECT statements.
   *
   * We essentially select all rows from the {test} table that have matching
   * rows in the {test_people} table based on the shared name column.
   */
  function testExistsSubquerySelect() {
    // Put George into {test_people}.
    db_insert('test_people')
      ->fields(array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
      ))
      ->execute();
    // Base query to {test}.
    $query = db_select('test', 't')
      ->fields('t', array('name'));
    // Subquery to {test_people}.
    $subquery = db_select('test_people', 'tp')
      ->fields('tp', array('name'))
      ->where('tp.name = t.name');
    $query->exists($subquery);
    $result = $query->execute();

    // Ensure that we got the right record.
    $record = $result->fetch();
    $this->assertEqual($record->name, 'George', 'Fetched name is correct using EXISTS query.');
  }

  /**
   * Tests NOT EXISTS subquery conditionals on SELECT statements.
   *
   * We essentially select all rows from the {test} table that don't have
   * matching rows in the {test_people} table based on the shared name column.
   */
  function testNotExistsSubquerySelect() {
    // Put George into {test_people}.
    db_insert('test_people')
      ->fields(array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
      ))
      ->execute();

    // Base query to {test}.
    $query = db_select('test', 't')
      ->fields('t', array('name'));
    // Subquery to {test_people}.
    $subquery = db_select('test_people', 'tp')
      ->fields('tp', array('name'))
      ->where('tp.name = t.name');
    $query->notExists($subquery);

    // Ensure that we got the right number of records.
    $people = $query->execute()->fetchCol();
    $this->assertEqual(count($people), 3, 'NOT EXISTS query returned the correct results.');
  }
}
