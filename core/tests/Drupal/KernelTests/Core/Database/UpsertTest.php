<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Upsert query builder.
 */
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class UpsertTest extends DatabaseTestBase {

  /**
   * Confirms that we can upsert (update-or-insert) records successfully.
   */
  public function testUpsert(): void {
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

    $result = $upsert->execute();
    $this->assertIsInt($result);
    $this->assertGreaterThanOrEqual(2, $result, 'The result of the upsert operation should report that at least two rows were affected.');

    $num_records_after = $connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Rows were inserted and updated properly.');

    $person = $connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Presenter'])->fetch();
    $this->assertEquals('Presenter', $person->job, 'Job set correctly.');
    $this->assertEquals(31, $person->age, 'Age set correctly.');
    $this->assertEquals('Tiffany', $person->name, 'Name set correctly.');

    $person = $connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEquals('Speaker', $person->job, 'Job was not changed.');
    $this->assertEquals(32, $person->age, 'Age updated correctly.');
    $this->assertEquals('Meredith', $person->name, 'Name was not changed.');
  }

  /**
   * Confirms that we can upsert records with keywords successfully.
   */
  public function testUpsertWithKeywords(): void {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {select}')->fetchField();

    $upsert = $this->connection->upsert('select')
      ->key('id')
      ->fields(['id', 'update']);

    // Add a new row.
    $upsert->values([
      // Test a non sequence ID for better testing of the default return value.
      'id' => 3,
      'update' => 'Update value 2',
    ]);

    // Update an existing row.
    $upsert->values([
      'id' => 1,
      'update' => 'Update value 1 updated',
    ]);

    $result = $upsert->execute();
    $this->assertIsInt($result);
    // The upsert returns the number of rows affected. For MySQL the return
    // value is 3 because the affected-rows value per row is 1 if the row is
    // inserted as a new row, 2 if an existing row is updated. See
    // https://dev.mysql.com/doc/c-api/8.0/en/mysql-affected-rows.html.
    $this->assertGreaterThanOrEqual(2, $result, 'The result of the upsert operation should report that at least two rows were affected.');

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {select}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Rows were inserted and updated properly.');

    $record = $this->connection->query('SELECT * FROM {select} WHERE [id] = :id', [':id' => 1])->fetch();
    $this->assertEquals('Update value 1 updated', $record->update);

    $record = $this->connection->query('SELECT * FROM {select} WHERE [id] = :id', [':id' => 3])->fetch();
    $this->assertEquals('Update value 2', $record->update);

    // An upsert should be re-usable.
    $upsert->values([
      'id' => 4,
      'update' => 'Another value',
    ]);
    $return_value = $upsert->execute();
    $this->assertSame(1, $return_value);
    $record = $this->connection->query('SELECT * FROM {select} WHERE [id] = :id', [':id' => 4])->fetch();
    $this->assertEquals('Another value', $record->update);
  }

  /**
   * Upsert on a not existing table throws a DatabaseExceptionWrapper.
   */
  public function testUpsertNonExistingTable(): void {
    $this->expectException(DatabaseExceptionWrapper::class);
    $upsert = $this->connection->upsert('a-table-that-does-not-exist')
      ->key('id')
      ->fields(['id', 'update']);
    $upsert->values([
      'id' => 1,
      'update' => 'Update value 1 updated',
    ]);
    $upsert->execute();
  }

  /**
   * Confirms that we can upsert records with composite keys successfully.
   */
  public function testCompositeKeyUpsert(): void {
    $connection = Database::getConnection();
    $this->installSchema('database_test', ['test_composite_primary']);

    // Add some initial test data.
    $connection->insert('test_composite_primary')
      ->fields(['name', 'age', 'job'])
      ->values([
        'name' => 'Tiffany',
        'age' => 31,
        'job' => 'Presenter',
      ])
      ->values([
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
      ])
      ->execute();

    $num_records_before = $connection->query('SELECT COUNT(*) FROM {test_composite_primary}')->fetchField();

    $upsert = $connection->upsert('test_composite_primary')
      ->key(['name', 'age'])
      // Add a new row directly from ::fields().
      ->fields([
        'name' => 'Kate',
        'age' => 25,
        'job' => 'Volunteer',
      ]);

    // Add a new row.
    $upsert->values([
      'name' => 'Karen',
      'age' => 35,
      'job' => 'Manager',
    ]);

    // Update an existing row.
    $upsert->values([
      'name' => 'Meredith',
      'age' => 30,
      // The initial job was 'Speaker'.
      'job' => 'Organizer',
    ]);

    // Add a new row by reusing a name but with a different age. This won't
    // match the composite primary key constraint.
    $upsert->values([
      'name' => 'Meredith',
      'age' => 40,
      'job' => 'Supervisor',
    ]);

    $upsert->execute();

    $num_records_after = $connection->query('SELECT COUNT(*) FROM {test_composite_primary}')->fetchField();
    $this->assertEquals($num_records_before + 3, $num_records_after, 'Rows were inserted and updated properly.');

    $person = $connection->query('SELECT * FROM {test_composite_primary} WHERE [job] = :job', [':job' => 'Volunteer'])->fetch();
    $this->assertEquals('Volunteer', $person->job, 'Job set correctly.');
    $this->assertEquals(25, $person->age, 'Age set correctly.');
    $this->assertEquals('Kate', $person->name, 'Name set correctly.');

    $person = $connection->query('SELECT * FROM {test_composite_primary} WHERE [job] = :job', [':job' => 'Manager'])->fetch();
    $this->assertEquals('Manager', $person->job, 'Job set correctly.');
    $this->assertEquals(35, $person->age, 'Age set correctly.');
    $this->assertEquals('Karen', $person->name, 'Name set correctly.');

    $person = $connection->query('SELECT * FROM {test_composite_primary} WHERE [job] = :job', [':job' => 'Organizer'])->fetch();
    $this->assertEquals('Organizer', $person->job, 'Job set correctly.');
    $this->assertEquals(30, $person->age, 'Age set correctly.');
    $this->assertEquals('Meredith', $person->name, 'Name set correctly.');

    $person = $connection->query('SELECT * FROM {test_composite_primary} WHERE [job] = :job', [':job' => 'Supervisor'])->fetch();
    $this->assertEquals('Supervisor', $person->job, 'Job set correctly.');
    $this->assertEquals(40, $person->age, 'Age set correctly.');
    $this->assertEquals('Meredith', $person->name, 'Name set correctly.');
  }

  /**
   * Tests that we can upsert a null into blob field.
   */
  public function testUpsertNullBlob(): void {
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => 'test'])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame('test', $r['blob1']);

    $this->connection->upsert('test_one_blob')
      ->key('id')
      ->fields(['id', 'blob1'])
      ->values(['id' => $id, 'blob1' => NULL])
      ->values(['id' => $id + 1, 'blob1' => NULL])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertNull($r['blob1']);
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id + 1])->fetchAssoc();
    $this->assertNull($r['blob1']);
  }

}
