<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\RowCountException;
use Drupal\user\Entity\User;

/**
 * Tests the Select query builder with more complex queries.
 *
 * @group Database
 */
class SelectComplexTest extends DatabaseTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node_access_test', 'field'];

  /**
   * Tests simple JOIN statements.
   */
  public function testDefaultJoin() {
    $query = $this->connection->select('test_task', 't');
    $people_alias = $query->join('test', 'p', '[t].[pid] = [p].[id]');
    $name_field = $query->addField($people_alias, 'name', 'name');
    $query->addField('t', 'task', 'task');
    $priority_field = $query->addField('t', 'priority', 'priority');

    $query->orderBy($priority_field);
    $result = $query->execute();

    $num_records = 0;
    $last_priority = 0;
    // Verify that the results are returned in the correct order.
    foreach ($result as $record) {
      $num_records++;
      $this->assertGreaterThanOrEqual($last_priority, $record->$priority_field);
      $this->assertNotSame('Ringo', $record->$name_field, 'Taskless person not selected.');
      $last_priority = $record->$priority_field;
    }

    $this->assertEquals(7, $num_records, 'Returned the correct number of rows.');
  }

  /**
   * Tests LEFT OUTER joins.
   */
  public function testLeftOuterJoin() {
    $query = $this->connection->select('test', 'p');
    $people_alias = $query->leftJoin('test_task', 't', '[t].[pid] = [p].[id]');
    $name_field = $query->addField('p', 'name', 'name');
    $query->addField($people_alias, 'task', 'task');
    $query->addField($people_alias, 'priority', 'priority');

    $query->orderBy($name_field);
    $result = $query->execute();

    $num_records = 0;
    $last_name = 0;

    // Verify that the results are returned in the correct order.
    foreach ($result as $record) {
      $num_records++;
      $this->assertGreaterThanOrEqual(0, strcmp($record->$name_field, $last_name));
    }

    $this->assertEquals(8, $num_records, 'Returned the correct number of rows.');
  }

  /**
   * Tests GROUP BY clauses.
   */
  public function testGroupBy() {
    $query = $this->connection->select('test_task', 't');
    $count_field = $query->addExpression('COUNT([task])', 'num');
    $task_field = $query->addField('t', 'task');
    $query->orderBy($count_field);
    $query->groupBy($task_field);
    $result = $query->execute();

    $num_records = 0;
    $last_count = 0;
    $records = [];
    // Verify that the results are returned in the correct order.
    foreach ($result as $record) {
      $num_records++;
      $this->assertGreaterThanOrEqual($last_count, $record->$count_field);
      $last_count = $record->$count_field;
      $records[$record->$task_field] = $record->$count_field;
    }

    $correct_results = [
      'eat' => 1,
      'sleep' => 2,
      'code' => 1,
      'found new band' => 1,
      'perform at superbowl' => 1,
    ];

    foreach ($correct_results as $task => $count) {
      $this->assertEquals($count, $records[$task], new FormattableMarkup("Correct number of '@task' records found.", ['@task' => $task]));
    }

    $this->assertEquals(6, $num_records, 'Returned the correct number of total rows.');
  }

  /**
   * Tests GROUP BY and HAVING clauses together.
   */
  public function testGroupByAndHaving() {
    $query = $this->connection->select('test_task', 't');
    $count_field = $query->addExpression('COUNT([task])', 'num');
    $task_field = $query->addField('t', 'task');
    $query->orderBy($count_field);
    $query->groupBy($task_field);
    $query->having('COUNT([task]) >= 2');
    $result = $query->execute();

    $num_records = 0;
    $last_count = 0;
    $records = [];
    // Verify that the results are returned in the correct order.
    foreach ($result as $record) {
      $num_records++;
      $this->assertGreaterThanOrEqual(2, $record->$count_field);
      $this->assertGreaterThanOrEqual($last_count, $record->$count_field);
      $last_count = $record->$count_field;
      $records[$record->$task_field] = $record->$count_field;
    }

    $correct_results = [
      'sleep' => 2,
    ];

    foreach ($correct_results as $task => $count) {
      $this->assertEquals($count, $records[$task], new FormattableMarkup("Correct number of '@task' records found.", ['@task' => $task]));
    }

    $this->assertEquals(1, $num_records, 'Returned the correct number of total rows.');
  }

  /**
   * Tests range queries.
   *
   * The SQL clause varies with the database.
   */
  public function testRange() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->range(0, 2);
    $query_result = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(2, $query_result, 'Returned the correct number of rows.');
  }

  /**
   * Tests whether the range property of a select clause can be undone.
   */
  public function testRangeUndo() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->range(0, 2);
    $query->range(NULL, NULL);
    $query_result = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(4, $query_result, 'Returned the correct number of rows.');
  }

  /**
   * Tests distinct queries.
   */
  public function testDistinct() {
    $query = $this->connection->select('test_task');
    $query->addField('test_task', 'task');
    $query->distinct();
    $query_result = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(6, $query_result, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can generate a count query from a built query.
   */
  public function testCountQuery() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy('name');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(4, $count, 'Counted the correct number of records.');

    // Now make sure we didn't break the original query!  We should still have
    // all of the fields we asked for.
    $record = $query->execute()->fetch();
    $this->assertEquals('George', $record->{$name_field}, 'Correct data retrieved.');
    $this->assertEquals(27, $record->{$age_field}, 'Correct data retrieved.');
  }

  /**
   * Tests having queries.
   */
  public function testHavingCountQuery() {
    $query = $this->connection->select('test')
      ->extend(PagerSelectExtender::class)
      ->groupBy('age')
      ->having('[age] + 1 > 0');
    $query->addField('test', 'age');
    $query->addExpression('[age] + 1');
    $count = count($query->execute()->fetchCol());
    $this->assertEquals(4, $count, 'Counted the correct number of records.');
  }

  /**
   * Tests that countQuery removes 'all_fields' statements and ordering clauses.
   */
  public function testCountQueryRemovals() {
    $query = $this->connection->select('test');
    $query->fields('test');
    $query->orderBy('name');
    $count = $query->countQuery();

    // Check that the 'all_fields' statement is handled properly.
    $tables = $query->getTables();
    $this->assertEquals(1, $tables['test']['all_fields'], 'Query correctly sets \'all_fields\' statement.');
    $tables = $count->getTables();
    $this->assertFalse(isset($tables['test']['all_fields']), 'Count query correctly unsets \'all_fields\' statement.');

    // Check that the ordering clause is handled properly.
    $orderby = $query->getOrderBy();
    // The orderby string is different for PostgreSQL.
    // @see Drupal\Core\Database\Driver\pgsql\Select::orderBy()
    $db_type = Database::getConnection()->databaseType();
    $this->assertEquals($db_type == 'pgsql' ? 'ASC NULLS FIRST' : 'ASC', $orderby['name'], 'Query correctly sets ordering clause.');
    $orderby = $count->getOrderBy();
    $this->assertFalse(isset($orderby['name']), 'Count query correctly unsets ordering clause.');

    // Make sure that the count query works.
    $count = $count->execute()->fetchField();

    $this->assertEquals(4, $count, 'Counted the correct number of records.');
  }

  /**
   * Tests that countQuery properly removes fields and expressions.
   */
  public function testCountQueryFieldRemovals() {
    // countQuery should remove all fields and expressions, so this can be
    // tested by adding a non-existent field and expression: if it ends
    // up in the query, an error will be thrown. If not, it will return the
    // number of records, which in this case happens to be 4 (there are four
    // records in the {test} table).
    $query = $this->connection->select('test');
    $query->fields('test', ['fail']);
    $this->assertEquals(4, $query->countQuery()->execute()->fetchField(), 'Count Query removed fields');

    $query = $this->connection->select('test');
    $query->addExpression('[fail]');
    $this->assertEquals(4, $query->countQuery()->execute()->fetchField(), 'Count Query removed expressions');
  }

  /**
   * Tests that we can generate a count query from a query with distinct.
   */
  public function testCountQueryDistinct() {
    $query = $this->connection->select('test_task');
    $query->addField('test_task', 'task');
    $query->distinct();

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(6, $count, 'Counted the correct number of records.');
  }

  /**
   * Tests that we can generate a count query from a query with GROUP BY.
   */
  public function testCountQueryGroupBy() {
    $query = $this->connection->select('test_task');
    $query->addField('test_task', 'pid');
    $query->groupBy('pid');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(3, $count, 'Counted the correct number of records.');

    // Use a column alias as, without one, the query can succeed for the wrong
    // reason.
    $query = $this->connection->select('test_task');
    $query->addField('test_task', 'pid', 'pid_alias');
    $query->addExpression('COUNT([test_task].[task])', 'count');
    $query->groupBy('pid_alias');
    $query->orderBy('pid_alias', 'asc');

    $count = $query->countQuery()->execute()->fetchField();

    $this->assertEquals(3, $count, 'Counted the correct number of records.');
  }

  /**
   * Confirms that we can properly nest conditional clauses.
   */
  public function testNestedConditions() {
    // This query should translate to:
    // "SELECT job FROM {test} WHERE name = 'Paul' AND (age = 26 OR age = 27)"
    // That should find only one record. Yes it's a non-optimal way of writing
    // that query but that's not the point!
    $query = $this->connection->select('test');
    $query->addField('test', 'job');
    $query->condition('name', 'Paul');
    $query->condition(($this->connection->condition('OR'))->condition('age', 26)->condition('age', 27));

    $job = $query->execute()->fetchField();
    $this->assertEquals('Songwriter', $job, 'Correct data retrieved.');
  }

  /**
   * Confirms we can join on a single table twice with a dynamic alias.
   */
  public function testJoinTwice() {
    $query = $this->connection->select('test')->fields('test');
    $alias = $query->join('test', 'test', '[test].[job] = [%alias].[job]');
    $query->addField($alias, 'name', 'other_name');
    $query->addField($alias, 'job', 'other_job');
    $query->where("[$alias].[name] <> [test].[name]");
    $crowded_job = $query->execute()->fetch();
    $this->assertEquals($crowded_job->other_job, $crowded_job->job, 'Correctly joined same table twice.');
    $this->assertNotEquals($crowded_job->other_name, $crowded_job->name, 'Correctly joined same table twice.');
  }

  /**
   * Tests that we can join on a query.
   */
  public function testJoinSubquery() {
    $this->installSchema('system', 'sequences');

    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);

    $query = Database::getConnection('replica')->select('test_task', 'tt');
    $query->addExpression('[tt].[pid] + 1', 'abc');
    $query->condition('priority', 1, '>');
    $query->condition('priority', 100, '<');

    $subquery = $this->connection->select('test', 'tp');
    $subquery->join('test_one_blob', 'tpb', '[tp].[id] = [tpb].[id]');
    $subquery->join('node', 'n', '[tp].[id] = [n].[nid]');
    $subquery->addTag('node_access');
    $subquery->addMetaData('account', $account);
    $subquery->addField('tp', 'id');
    $subquery->condition('age', 5, '>');
    $subquery->condition('age', 500, '<');

    $query->leftJoin($subquery, 'sq', '[tt].[pid] = [sq].[id]');
    $query->join('test_one_blob', 'tb3', '[tt].[pid] = [tb3].[id]');

    // Construct the query string.
    // This is the same sequence that SelectQuery::execute() goes through.
    $query->preExecute();
    $query->getArguments();
    $str = (string) $query;

    // Verify that the string only has one copy of condition placeholder 0.
    $pos = strpos($str, 'db_condition_placeholder_0', 0);
    $pos2 = strpos($str, 'db_condition_placeholder_0', $pos + 1);
    $this->assertFalse($pos2, 'Condition placeholder is not repeated.');
  }

  /**
   * Tests that rowCount() throws exception on SELECT query.
   */
  public function testSelectWithRowCount() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $result = $query->execute();
    try {
      $result->rowCount();
      $exception = FALSE;
    }
    catch (RowCountException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, 'Exception was thrown');
  }

  /**
   * Tests that join conditions can use Condition objects.
   */
  public function testJoinConditionObject() {
    // Same test as testDefaultJoin, but with a Condition object.
    $query = $this->connection->select('test_task', 't');
    $join_cond = ($this->connection->condition('AND'))->where('[t].[pid] = [p].[id]');
    $people_alias = $query->join('test', 'p', $join_cond);
    $name_field = $query->addField($people_alias, 'name', 'name');
    $query->addField('t', 'task', 'task');
    $priority_field = $query->addField('t', 'priority', 'priority');

    $query->orderBy($priority_field);
    $result = $query->execute();

    $num_records = 0;
    $last_priority = 0;
    foreach ($result as $record) {
      $num_records++;
      // Verify that the results are returned in the correct order.
      $this->assertGreaterThanOrEqual($last_priority, $record->$priority_field);
      $this->assertNotSame('Ringo', $record->$name_field, 'Taskless person not selected.');
      $last_priority = $record->$priority_field;
    }

    $this->assertEquals(7, $num_records, 'Returned the correct number of rows.');

    // Test a condition object that creates placeholders.
    $t1_name = 'John';
    $t2_name = 'George';
    $join_cond = ($this->connection->condition('AND'))
      ->condition('t1.name', $t1_name)
      ->condition('t2.name', $t2_name);
    $query = $this->connection->select('test', 't1');
    $query->innerJoin('test', 't2', $join_cond);
    $query->addField('t1', 'name', 't1_name');
    $query->addField('t2', 'name', 't2_name');

    $num_records = $query->countQuery()->execute()->fetchField();
    $this->assertEquals(1, $num_records, 'Query expected to return 1 row. Actual: ' . $num_records);
    if ($num_records == 1) {
      $record = $query->execute()->fetchObject();
      $this->assertEquals($t1_name, $record->t1_name, 'Query expected to retrieve name ' . $t1_name . ' from table t1. Actual: ' . $record->t1_name);
      $this->assertEquals($t2_name, $record->t2_name, 'Query expected to retrieve name ' . $t2_name . ' from table t2. Actual: ' . $record->t2_name);
    }
  }

}
