<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * Tests handling of some invalid data.
 *
 * @group Database
 */
class InvalidDataTest extends DatabaseTestBase {
  /**
   * Tests aborting of traditional SQL database systems with invalid data.
   */
  public function testInsertDuplicateData() {
    // Try to insert multiple records where at least one has bad data.
    try {
      db_insert('test')
        ->fields(['name', 'age', 'job'])
        ->values([
          'name' => 'Elvis',
          'age' => 63,
          'job' => 'Singer',
        ])->values([
          // Duplicate value on unique field.
          'name' => 'John',
          'age' => 17,
          'job' => 'Consultant',
        ])
        ->values([
          'name' => 'Frank',
          'age' => 75,
          'job' => 'Singer',
        ])
        ->execute();
      $this->fail('Insert succeeded when it should not have.');
    }
    catch (IntegrityConstraintViolationException $e) {
      // Check if the first record was inserted.
      $name = db_query('SELECT name FROM {test} WHERE age = :age', [':age' => 63])->fetchField();

      if ($name == 'Elvis') {
        if (!Database::getConnection()->supportsTransactions()) {
          // This is an expected fail.
          // Database engines that don't support transactions can leave partial
          // inserts in place when an error occurs. This is the case for MySQL
          // when running on a MyISAM table.
          $this->pass("The whole transaction has not been rolled-back when a duplicate key insert occurs, this is expected because the database doesn't support transactions");
        }
        else {
          $this->fail('The whole transaction is rolled back when a duplicate key insert occurs.');
        }
      }
      else {
        $this->pass('The whole transaction is rolled back when a duplicate key insert occurs.');
      }

      // Ensure the other values were not inserted.
      $record = db_select('test')
        ->fields('test', ['name', 'age'])
        ->condition('age', [17, 75], 'IN')
        ->execute()->fetchObject();

      $this->assertFalse($record, 'The rest of the insert aborted as expected.');
    }
  }

  /**
   * Tests inserting with invalid data from a select query.
   */
  public function testInsertDuplicateDataFromSelect() {
    // Insert multiple records in 'test_people' where one has bad data
    // (duplicate key). A 'Meredith' record has already been inserted
    // in ::setUp.
    db_insert('test_people')
      ->fields(['name', 'age', 'job'])
      ->values([
        'name' => 'Elvis',
        'age' => 63,
        'job' => 'Singer',
      ])->values([
        // Duplicate value on unique field 'name' for later INSERT in 'test'
        // table.
        'name' => 'John',
        'age' => 17,
        'job' => 'Consultant',
      ])
      ->values([
        'name' => 'Frank',
        'age' => 75,
        'job' => 'Bass',
      ])
      ->execute();

    try {
      // Define the subselect query. Add ORDER BY to ensure we have consistent
      // order in results. Will return:
      // 0 => [name] => Elvis, [age] => 63, [job] => Singer
      // 1 => [name] => Frank, [age] => 75, [job] => Bass
      // 2 => [name] => John, [age] => 17, [job] => Consultant
      // 3 => [name] => Meredith, [age] => 30, [job] => Speaker
      // Records 0 and 1 should pass, record 2 should lead to integrity
      // constraint violation.
      $query = db_select('test_people', 'tp')
        ->fields('tp', ['name', 'age', 'job'])
        ->orderBy('name');

      // Try inserting from the subselect.
      db_insert('test')
        ->from($query)
        ->execute();

      $this->fail('Insert succeeded when it should not have.');
    }
    catch (IntegrityConstraintViolationException $e) {
      // Check if the second record was inserted.
      $name = db_query('SELECT name FROM {test} WHERE age = :age', [':age' => 75])->fetchField();

      if ($name == 'Frank') {
        if (!Database::getConnection()->supportsTransactions()) {
          // This is an expected fail.
          // Database engines that don't support transactions can leave partial
          // inserts in place when an error occurs. This is the case for MySQL
          // when running on a MyISAM table.
          $this->pass("The whole transaction has not been rolled-back when a duplicate key insert occurs, this is expected because the database doesn't support transactions");
        }
        else {
          $this->fail('The whole transaction is rolled back when a duplicate key insert occurs.');
        }
      }
      else {
        $this->pass('The whole transaction is rolled back when a duplicate key insert occurs.');
      }

      // Ensure the values for records 2 and 3 were not inserted.
      $record = db_select('test')
        ->fields('test', ['name', 'age'])
        ->condition('age', [17, 30], 'IN')
        ->execute()->fetchObject();

      $this->assertFalse($record, 'The rest of the insert aborted as expected.');
    }
  }

}
