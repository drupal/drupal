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
  function testSimpleAlter() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->addTag('database_test_alter_add_range');

    $result = $query->execute()->fetchAll();

    $this->assertEqual(count($result), 2, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can alter the joins on a query.
   */
  function testAlterWithJoin() {
    $query = db_select('test_task');
    $tid_field = $query->addField('test_task', 'tid');
    $task_field = $query->addField('test_task', 'task');
    $query->orderBy($task_field);
    $query->addTag('database_test_alter_add_join');

    $result = $query->execute();

    $records = $result->fetchAll();

    $this->assertEqual(count($records), 2, 'Returned the correct number of rows.');

    $this->assertEqual($records[0]->name, 'George', 'Correct data retrieved.');
    $this->assertEqual($records[0]->$tid_field, 4, 'Correct data retrieved.');
    $this->assertEqual($records[0]->$task_field, 'sing', 'Correct data retrieved.');
    $this->assertEqual($records[1]->name, 'George', 'Correct data retrieved.');
    $this->assertEqual($records[1]->$tid_field, 5, 'Correct data retrieved.');
    $this->assertEqual($records[1]->$task_field, 'sleep', 'Correct data retrieved.');
  }

  /**
   * Tests that we can alter a query's conditionals.
   */
  function testAlterChangeConditional() {
    $query = db_select('test_task');
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

    $this->assertEqual(count($records), 1, 'Returned the correct number of rows.');
    $this->assertEqual($records[0]->$name_field, 'John', 'Correct data retrieved.');
    $this->assertEqual($records[0]->$tid_field, 2, 'Correct data retrieved.');
    $this->assertEqual($records[0]->$pid_field, 1, 'Correct data retrieved.');
    $this->assertEqual($records[0]->$task_field, 'sleep', 'Correct data retrieved.');
  }

  /**
   * Tests that we can alter the fields of a query.
   */
  function testAlterChangeFields() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy('name');
    $query->addTag('database_test_alter_change_fields');

    $record = $query->execute()->fetch();
    $this->assertEqual($record->$name_field, 'George', 'Correct data retrieved.');
    $this->assertFalse(isset($record->$age_field), 'Age field not found, as intended.');
  }

  /**
   * Tests that we can alter expressions in the query.
   */
  function testAlterExpression() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addExpression("age*2", 'double_age');
    $query->condition('age', 27);
    $query->addTag('database_test_alter_change_expressions');
    $result = $query->execute();

    // Ensure that we got the right record.
    $record = $result->fetch();

    $this->assertEqual($record->$name_field, 'George', 'Fetched name is correct.');
    $this->assertEqual($record->$age_field, 27*3, 'Fetched age expression is correct.');
  }

  /**
   * Tests that we can remove a range() value from a query.
   *
   * This also tests hook_query_TAG_alter().
   */
  function testAlterRemoveRange() {
    $query = db_select('test');
    $query->addField('test', 'name');
    $query->addField('test', 'age', 'age');
    $query->range(0, 2);
    $query->addTag('database_test_alter_remove_range');

    $num_records = count($query->execute()->fetchAll());

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

  /**
   * Tests that we can do basic alters on subqueries.
   */
  function testSimpleAlterSubquery() {
    // Create a sub-query with an alter tag.
    $subquery = db_select('test', 'p');
    $subquery->addField('p', 'name');
    $subquery->addField('p', 'id');
    // Pick out George.
    $subquery->condition('age', 27);
    $subquery->addExpression("age*2", 'double_age');
    // This query alter should change it to age * 3.
    $subquery->addTag('database_test_alter_change_expressions');

    // Create a main query and join to sub-query.
    $query = db_select('test_task', 'tt');
    $query->join($subquery, 'pq', 'pq.id = tt.pid');
    $age_field = $query->addField('pq', 'double_age');
    $name_field = $query->addField('pq', 'name');

    $record = $query->execute()->fetch();
    $this->assertEqual($record->$name_field, 'George', 'Fetched name is correct.');
    $this->assertEqual($record->$age_field, 27*3, 'Fetched age expression is correct.');
  }
}
