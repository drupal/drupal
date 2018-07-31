<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests the Upsert query builder.
 *
 * @group Database
 */
class UpsertTest extends DatabaseTestBase {

  /**
   * Confirms that we can upsert (update-or-insert) records successfully.
   */
  public function testUpsert() {
    $connection = Database::getConnection();
    $num_records_before = $connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $upsert = $connection->upsert('test_people')
      ->key('job')
      ->fields(['job', 'age', 'name']);

    // Add a new row.
    $upsert->values([
      'job' => 'Presenter',
      'age' => 31,
      'name' => 'Tiffany',
    ]);

    // Update an existing row.
    $upsert->values([
      'job' => 'Speaker',
      // The initial age was 30.
      'age' => 32,
      'name' => 'Meredith',
    ]);

    $upsert->execute();

    $num_records_after = $connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before + 1, $num_records_after, 'Rows were inserted and updated properly.');

    $person = $connection->query('SELECT * FROM {test_people} WHERE job = :job', [':job' => 'Presenter'])->fetch();
    $this->assertEqual($person->job, 'Presenter', 'Job set correctly.');
    $this->assertEqual($person->age, 31, 'Age set correctly.');
    $this->assertEqual($person->name, 'Tiffany', 'Name set correctly.');

    $person = $connection->query('SELECT * FROM {test_people} WHERE job = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->job, 'Speaker', 'Job was not changed.');
    $this->assertEqual($person->age, 32, 'Age updated correctly.');
    $this->assertEqual($person->name, 'Meredith', 'Name was not changed.');
  }

  /**
   * Tests that we can upsert records with a special named column.
   */
  public function testSpecialColumnUpsert() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_special_columns}')->fetchField();
    $upsert = $this->connection->upsert('test_special_columns')
      ->key('id')
      ->fields(['id', 'offset', 'function']);

    // Add a new row.
    $upsert->values([
      'id' => 2,
      'offset' => 'Offset 2',
      'function' => 'Function 2',
    ]);

    // Update an existing row.
    $upsert->values([
      'id' => 1,
      'offset' => 'Offset 1 updated',
      'function' => 'Function 1 updated',
    ]);

    $upsert->execute();
    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_special_columns}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Rows were inserted and updated properly.');

    $record = $this->connection->query('SELECT * FROM {test_special_columns} WHERE id = :id', [':id' => 1])->fetch();
    $this->assertEquals($record->offset, 'Offset 1 updated');
    $this->assertEquals($record->function, 'Function 1 updated');

    $record = $this->connection->query('SELECT * FROM {test_special_columns} WHERE id = :id', [':id' => 2])->fetch();
    $this->assertEquals($record->offset, 'Offset 2');
    $this->assertEquals($record->function, 'Function 2');
  }

}
