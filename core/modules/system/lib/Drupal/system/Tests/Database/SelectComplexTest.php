<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectComplexTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Test more complex select statements.
 */
class SelectComplexTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Select tests, complex',
      'description' => 'Test the Select query builder with more complex queries.',
      'group' => 'Database',
    );
  }

  function setUp() {
    parent::setUp(array('node_access_test'));
  }

  /**
   * Test simple JOIN statements.
   */
  function testDefaultJoin() {
    $query = db_select('test_task', 't');
    $people_alias = $query->join('test', 'p', 't.pid = p.id');
    $name_field = $query->addField($people_alias, 'name', 'name');
    $task_field = $query->addField('t', 'task', 'task');
    $priority_field = $query->addField('t', 'priority', 'priority');

    $query->orderBy($priority_field);
    $result = $query->execute();

    $num_records = 0;
    $last_priority = 0;
    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue($record->$priority_field >= $last_priority, t('Results returned in correct order.'));
      $this->assertNotEqual($record->$name_field, 'Ringo', t('Taskless person not selected.'));
      $last_priority = $record->$priority_field;
    }

    $this->assertEqual($num_records, 7, t('Returned the correct number of rows.'));
  }

  /**
   * Test LEFT OUTER joins.
   */
  function testLeftOuterJoin() {
    $query = db_select('test', 'p');
    $people_alias = $query->leftJoin('test_task', 't', 't.pid = p.id');
    $name_field = $query->addField('p', 'name', 'name');
    $task_field = $query->addField($people_alias, 'task', 'task');
    $priority_field = $query->addField($people_alias, 'priority', 'priority');

    $query->orderBy($name_field);
    $result = $query->execute();

    $num_records = 0;
    $last_name = 0;

    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue(strcmp($record->$name_field, $last_name) >= 0, t('Results returned in correct order.'));
      $last_priority = $record->$name_field;
    }

    $this->assertEqual($num_records, 8, t('Returned the correct number of rows.'));
  }

  /**
   * Test GROUP BY clauses.
   */
  function testGroupBy() {
    $query = db_select('test_task', 't');
    $count_field = $query->addExpression('COUNT(task)', 'num');
    $task_field = $query->addField('t', 'task');
    $query->orderBy($count_field);
    $query->groupBy($task_field);
    $result = $query->execute();

    $num_records = 0;
    $last_count = 0;
    $records = array();
    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue($record->$count_field >= $last_count, t('Results returned in correct order.'));
      $last_count = $record->$count_field;
      $records[$record->$task_field] = $record->$count_field;
    }

    $correct_results = array(
      'eat' => 1,
      'sleep' => 2,
      'code' => 1,
      'found new band' => 1,
      'perform at superbowl' => 1,
    );

    foreach ($correct_results as $task => $count) {
      $this->assertEqual($records[$task], $count, t("Correct number of '@task' records found.", array('@task' => $task)));
    }

    $this->assertEqual($num_records, 6, t('Returned the correct number of total rows.'));
  }

  /**
   * Test GROUP BY and HAVING clauses together.
   */
  function testGroupByAndHaving() {
    $query = db_select('test_task', 't');
    $count_field = $query->addExpression('COUNT(task)', 'num');
    $task_field = $query->addField('t', 'task');
    $query->orderBy($count_field);
    $query->groupBy($task_field);
    $query->having('COUNT(task) >= 2');
    $result = $query->execute();

    $num_records = 0;
    $last_count = 0;
    $records = array();
    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue($record->$count_field >= 2, t('Record has the minimum count.'));
      $this->assertTrue($record->$count_field >= $last_count, t('Results returned in correct order.'));
      $last_count = $record->$count_field;
      $records[$record->$task_field] = $record->$count_field;
    }

    $correct_results = array(
      'sleep' => 2,
    );

    foreach ($correct_results as $task => $count) {
      $this->assertEqual($records[$task], $count, t("Correct number of '@task' records found.", array('@task' => $task)));
    }

    $this->assertEqual($num_records, 1, t('Returned the correct number of total rows.'));
  }

  /**
   * Test range queries. The SQL clause varies with the database.
   */
  function testRange() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->range(0, 2);
    $result = $query->execute();

    $num_records = 0;
    foreach ($result as $record) {
      $num_records++;
    }

    $this->assertEqual($num_records, 2, t('Returned the correct number of rows.'));
  }

  /**
   * Test distinct queries.
   */
  function testDistinct() {
    $query = db_select('test_task');
    $task_field = $query->addField('test_task', 'task');
    $query->distinct();
    $result = $query->execute();

    $num_records = 0;
    foreach ($result as $record) {
      $num_records++;
    }

    $this->assertEqual($num_records, 6, t('Returned the correct number of rows.'));
  }

  /**
   * Test that we can generate a count query from a built query.
   */
  function testCountQuery() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy('name');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEqual($count, 4, t('Counted the correct number of records.'));

    // Now make sure we didn't break the original query!  We should still have
    // all of the fields we asked for.
    $record = $query->execute()->fetch();
    $this->assertEqual($record->$name_field, 'George', t('Correct data retrieved.'));
    $this->assertEqual($record->$age_field, 27, t('Correct data retrieved.'));
  }

  function testHavingCountQuery() {
    $query = db_select('test')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->groupBy('age')
      ->having('age + 1 > 0');
    $query->addField('test', 'age');
    $query->addExpression('age + 1');
    $count = count($query->execute()->fetchCol());
    $this->assertEqual($count, 4, t('Counted the correct number of records.'));
  }

  /**
   * Test that countQuery properly removes 'all_fields' statements and
   * ordering clauses.
   */
  function testCountQueryRemovals() {
    $query = db_select('test');
    $query->fields('test');
    $query->orderBy('name');
    $count = $query->countQuery();

    // Check that the 'all_fields' statement is handled properly.
    $tables = $query->getTables();
    $this->assertEqual($tables['test']['all_fields'], 1, t('Query correctly sets \'all_fields\' statement.'));
    $tables = $count->getTables();
    $this->assertFalse(isset($tables['test']['all_fields']), t('Count query correctly unsets \'all_fields\' statement.'));

    // Check that the ordering clause is handled properly.
    $orderby = $query->getOrderBy();
    $this->assertEqual($orderby['name'], 'ASC', t('Query correctly sets ordering clause.'));
    $orderby = $count->getOrderBy();
    $this->assertFalse(isset($orderby['name']), t('Count query correctly unsets ordering caluse.'));

    // Make sure that the count query works.
    $count = $count->execute()->fetchField();

    $this->assertEqual($count, 4, t('Counted the correct number of records.'));
  }


  /**
   * Test that countQuery properly removes fields and expressions.
   */
  function testCountQueryFieldRemovals() {
    // countQuery should remove all fields and expressions, so this can be
    // tested by adding a non-existent field and expression: if it ends
    // up in the query, an error will be thrown. If not, it will return the
    // number of records, which in this case happens to be 4 (there are four
    // records in the {test} table).
    $query = db_select('test');
    $query->fields('test', array('fail'));
    $this->assertEqual(4, $query->countQuery()->execute()->fetchField(), t('Count Query removed fields'));

    $query = db_select('test');
    $query->addExpression('fail');
    $this->assertEqual(4, $query->countQuery()->execute()->fetchField(), t('Count Query removed expressions'));
  }

  /**
   * Test that we can generate a count query from a query with distinct.
   */
  function testCountQueryDistinct() {
    $query = db_select('test_task');
    $task_field = $query->addField('test_task', 'task');
    $query->distinct();

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEqual($count, 6, t('Counted the correct number of records.'));
  }

  /**
   * Test that we can generate a count query from a query with GROUP BY.
   */
  function testCountQueryGroupBy() {
    $query = db_select('test_task');
    $pid_field = $query->addField('test_task', 'pid');
    $query->groupBy('pid');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEqual($count, 3, t('Counted the correct number of records.'));

    // Use a column alias as, without one, the query can succeed for the wrong
    // reason.
    $query = db_select('test_task');
    $pid_field = $query->addField('test_task', 'pid', 'pid_alias');
    $query->addExpression('COUNT(test_task.task)', 'count');
    $query->groupBy('pid_alias');
    $query->orderBy('pid_alias', 'asc');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEqual($count, 3, t('Counted the correct number of records.'));
  }

  /**
   * Confirm that we can properly nest conditional clauses.
   */
  function testNestedConditions() {
    // This query should translate to:
    // "SELECT job FROM {test} WHERE name = 'Paul' AND (age = 26 OR age = 27)"
    // That should find only one record. Yes it's a non-optimal way of writing
    // that query but that's not the point!
    $query = db_select('test');
    $query->addField('test', 'job');
    $query->condition('name', 'Paul');
    $query->condition(db_or()->condition('age', 26)->condition('age', 27));

    $job = $query->execute()->fetchField();
    $this->assertEqual($job, 'Songwriter', t('Correct data retrieved.'));
  }

  /**
   * Confirm we can join on a single table twice with a dynamic alias.
   */
  function testJoinTwice() {
    $query = db_select('test')->fields('test');
    $alias = $query->join('test', 'test', 'test.job = %alias.job');
    $query->addField($alias, 'name', 'othername');
    $query->addField($alias, 'job', 'otherjob');
    $query->where("$alias.name <> test.name");
    $crowded_job = $query->execute()->fetch();
    $this->assertEqual($crowded_job->job, $crowded_job->otherjob, t('Correctly joined same table twice.'));
    $this->assertNotEqual($crowded_job->name, $crowded_job->othername, t('Correctly joined same table twice.'));
  }

  /**
   * Test that we can join on a query.
   */
  function testJoinSubquery() {
    $acct = $this->drupalCreateUser();
    $this->drupalLogin($acct);

    $query = db_select('test_task', 'tt', array('target' => 'slave'));
    $query->addExpression('tt.pid + 1', 'abc');
    $query->condition('priority', 1, '>');
    $query->condition('priority', 100, '<');

    $subquery = db_select('test', 'tp');
    $subquery->join('test_one_blob', 'tpb', 'tp.id = tpb.id');
    $subquery->join('node', 'n', 'tp.id = n.nid');
    $subquery->addTag('node_access');
    $subquery->addMetaData('account', $acct);
    $subquery->addField('tp', 'id');
    $subquery->condition('age', 5, '>');
    $subquery->condition('age', 500, '<');

    $query->leftJoin($subquery, 'sq', 'tt.pid = sq.id');
    $query->join('test_one_blob', 'tb3', 'tt.pid = tb3.id');

    // Construct the query string.
    // This is the same sequence that SelectQuery::execute() goes through.
    $query->preExecute();
    $query->getArguments();
    $str = (string) $query;

    // Verify that the string only has one copy of condition placeholder 0.
    $pos = strpos($str, 'db_condition_placeholder_0', 0);
    $pos2 = strpos($str, 'db_condition_placeholder_0', $pos + 1);
    $this->assertFalse($pos2, "Condition placeholder is not repeated");
  }
}
