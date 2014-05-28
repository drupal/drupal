<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\DeleteTruncateTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests delete and truncate queries.
 *
 * The DELETE tests are not as extensive, as all of the interesting code for
 * DELETE queries is in the conditional which is identical to the UPDATE and
 * SELECT conditional handling.
 *
 * The TRUNCATE tests are not extensive either, because the behavior of
 * TRUNCATE queries is not consistent across database engines. We only test
 * that a TRUNCATE query actually deletes all rows from the target table.
 */
class DeleteTruncateTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Delete/Truncate tests',
      'description' => 'Test the Delete and Truncate query builders.',
      'group' => 'Database',
    );
  }

  /**
   * Confirms that we can use a subselect in a delete successfully.
   */
  function testSubselectDelete() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_task}')->fetchField();
    $pid_to_delete = db_query("SELECT * FROM {test_task} WHERE task = 'sleep'")->fetchField();

    $subquery = db_select('test', 't')
      ->fields('t', array('id'))
      ->condition('t.id', array($pid_to_delete), 'IN');
    $delete = db_delete('test_task')
      ->condition('task', 'sleep')
      ->condition('pid', $subquery, 'IN');

    $num_deleted = $delete->execute();
    $this->assertEqual($num_deleted, 1, 'Deleted 1 record.');

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_task}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after + $num_deleted, 'Deletion adds up.');
  }

  /**
   * Confirms that we can delete a single record successfully.
   */
  function testSimpleDelete() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    $num_deleted = db_delete('test')
      ->condition('id', 1)
      ->execute();
    $this->assertIdentical($num_deleted, 1, 'Deleted 1 record.');

    $num_records_after = db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after + $num_deleted, 'Deletion adds up.');
  }

  /**
   * Confirms that we can truncate a whole table successfully.
   */
  function testTruncate() {
    $num_records_before = db_query("SELECT COUNT(*) FROM {test}")->fetchField();
    $this->assertTrue($num_records_before > 0, 'The table is not empty.');

    db_truncate('test')->execute();

    $num_records_after = db_query("SELECT COUNT(*) FROM {test}")->fetchField();
    $this->assertEqual(0, $num_records_after, 'Truncate really deletes everything.');
  }
}
