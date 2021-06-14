<?php

namespace Drupal\KernelTests\Core\Database;

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
    $this->expectException(IntegrityConstraintViolationException::class);
    try {
      $this->connection->insert('test')
        ->fields(['name', 'age', 'job'])
        ->values([
          'name' => 'Elvis',
          'age' => 63,
          'job' => 'Singer',
        ])
        ->values([
          // Duplicate value 'John' on unique field 'name'.
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
      // Ensure the whole transaction is rolled back when a duplicate key
      // insert occurs.
      $this->assertFalse($this->connection->select('test')
        ->fields('test', ['name', 'age'])
        ->condition('age', [63, 17, 75], 'IN')
        ->execute()->fetchObject());
      throw $e;
    }
  }

  /**
   * Tests inserting with invalid data from a select query.
   */
  public function testInsertDuplicateDataFromSelect() {
    // Insert multiple records in 'test_people' where one has bad data
    // (duplicate key). A 'Meredith' record has already been inserted
    // in ::setUp.
    $this->connection->insert('test_people')
      ->fields(['name', 'age', 'job'])
      ->values([
        'name' => 'Elvis',
        'age' => 63,
        'job' => 'Singer',
      ])
      ->values([
        // Duplicate value 'John' on unique field 'name' for later INSERT in
        // 'test' table.
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

    // Define the subselect query. Add ORDER BY to ensure we have consistent
    // order in results. Will return:
    // 0 => [name] => Elvis, [age] => 63, [job] => Singer
    // 1 => [name] => Frank, [age] => 75, [job] => Bass
    // 2 => [name] => John, [age] => 17, [job] => Consultant
    // 3 => [name] => Meredith, [age] => 30, [job] => Speaker
    // Records 0 and 1 should pass, record 2 should lead to integrity
    // constraint violation.
    $query = $this->connection->select('test_people', 'tp')
      ->fields('tp', ['name', 'age', 'job'])
      ->orderBy('name');

    // Try inserting from the subselect.
    $this->expectException(IntegrityConstraintViolationException::class);
    try {
      $this->connection->insert('test')
        ->from($query)
        ->execute();
      $this->fail('Insert succeeded when it should not have.');
    }
    catch (IntegrityConstraintViolationException $e) {
      // Ensure the whole transaction is rolled back when a duplicate key
      // insert occurs.
      $this->assertFalse($this->connection->select('test')
        ->fields('test', ['name', 'age'])
        ->condition('age', [63, 75, 17, 30], 'IN')
        ->execute()->fetchObject());
      throw $e;
    }
  }

}
