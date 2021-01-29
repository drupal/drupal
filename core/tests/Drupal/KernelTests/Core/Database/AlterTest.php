<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the hook_query_alter capabilities of the Select builder.
 *
 * @group Database
 * @see database_test_query_alter()
 */
class AlterTest extends DatabaseTestBase {

  /**
   * Tests that we can do basic alters.
   */
  public function testSimpleAlter() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->addTag('database_test_alter_add_range');

    $result = $query->execute()->fetchAll();

    $this->assertCount(2, $result, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can alter the joins on a query.
   */
  public function testAlterWithJoin() {
    $query = $this->connection->select('test_task');
    $tid_field = $query->addField('test_task', 'tid');
    $task_field = $query->addField('test_task', 'task');
    $query->orderBy($task_field);
    $query->addTag('database_test_alter_add_join');

    $result = $query->execute();

    $records = $result->fetchAll();

    $this->assertCount(2, $records, 'Returned the correct number of rows.');

    $this->assertEqual('George', $records[0]->name, 'Correct data retrieved.');
    $this->assertEqual(4, $records[0]->{$tid_field}, 'Correct data retrieved.');
    $this->assertEqual('sing', $records[0]->{$task_field}, 'Correct data retrieved.');
    $this->assertEqual('George', $records[1]->name, 'Correct data retrieved.');
    $this->assertEqual(5, $records[1]->{$tid_field}, 'Correct data retrieved.');
    $this->assertEqual('sleep', $records[1]->{$task_field}, 'Correct data retrieved.');
  }

  /**
   * Tests that we can alter a query's conditionals.
   */
  public function testAlterChangeConditional() {
    $query = $this->connection->select('test_task');
    $tid_field = $query->addField('test_task', 'tid');
    $pid_field = $query->addField('test_task', 'pid');
    $task_field = $query->addField('test_task', 'task');
    $people_alias = $query->join('test', 'people', "test_task.pid = people.id");
    $name_field = $query->addField($people_alias, 'name', 'name');
    $query->condition('test_task.tid', '1');
    $query->orderBy($tid_field);
    $query->addTag('database_test_alter_change_conditional');

    $result = $query->execute();

    $records = $result->fetchAll();

    $this->assertCount(1, $records, 'Returned the correct number of rows.');
    $this->assertEqual('John', $records[0]->{$name_field}, 'Correct data retrieved.');
    $this->assertEqual(2, $records[0]->{$tid_field}, 'Correct data retrieved.');
    $this->assertEqual(1, $records[0]->{$pid_field}, 'Correct data retrieved.');
    $this->assertEqual('sleep', $records[0]->{$task_field}, 'Correct data retrieved.');
  }

  /**
   * Tests that we can alter the fields of a query.
   */
  public function testAlterChangeFields() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy('name');
    $query->addTag('database_test_alter_change_fields');

    $record = $query->execute()->fetch();
    $this->assertEqual('George', $record->{$name_field}, 'Correct data retrieved.');
    $this->assertFalse(isset($record->$age_field), 'Age field not found, as intended.');
  }

  /**
   * Tests that we can alter expressions in the query.
   */
  public function testAlterExpression() {
    $query = $this->connection->select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addExpression("age*2", 'double_age');
    $query->condition('age', 27);
    $query->addTag('database_test_alter_change_expressions');
    $result = $query->execute();

    // Ensure that we got the right record.
    $record = $result->fetch();

    $this->assertEqual('George', $record->{$name_field}, 'Fetched name is correct.');
    $this->assertEqual(27 * 3, $record->{$age_field}, 'Fetched age expression is correct.');
  }

  /**
   * Tests that we can remove a range() value from a query.
   *
   * This also tests hook_query_TAG_alter().
   */
  public function testAlterRemoveRange() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->range(0, 2);
    $query->addTag('database_test_alter_remove_range');

    $num_records = count($query->execute()->fetchAll());

    $this->assertEqual(4, $num_records, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can do basic alters on subqueries.
   */
  public function testSimpleAlterSubquery() {
    // Create a sub-query with an alter tag.
    $subquery = $this->connection->select('test', 'p');
    $subquery->addField('p', 'name');
    $subquery->addField('p', 'id');
    // Pick out George.
    $subquery->condition('age', 27);
    $subquery->addExpression("age*2", 'double_age');
    // This query alter should change it to age * 3.
    $subquery->addTag('database_test_alter_change_expressions');

    // Create a main query and join to sub-query.
    $query = $this->connection->select('test_task', 'tt');
    $query->join($subquery, 'pq', 'pq.id = tt.pid');
    $age_field = $query->addField('pq', 'double_age');
    $name_field = $query->addField('pq', 'name');

    $record = $query->execute()->fetch();
    $this->assertEqual('George', $record->{$name_field}, 'Fetched name is correct.');
    $this->assertEqual(27 * 3, $record->{$age_field}, 'Fetched age expression is correct.');
  }

}
