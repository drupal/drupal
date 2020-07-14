<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\InvalidMergeQueryException;

/**
 * Tests the MERGE query builder.
 *
 * @group Database
 */
class MergeTest extends DatabaseTestBase {

  /**
   * Confirms that we can merge-insert a record successfully.
   */
  public function testMergeInsert() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $result = $this->connection->merge('test_people')
      ->key('job', 'Presenter')
      ->fields([
        'age' => 31,
        'name' => 'Tiffany',
      ])
      ->execute();

    $this->assertEqual($result, Merge::STATUS_INSERT, 'Insert status returned.');

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Presenter'])->fetch();
    $this->assertEqual($person->name, 'Tiffany', 'Name set correctly.');
    $this->assertEqual($person->age, 31, 'Age set correctly.');
    $this->assertEqual($person->job, 'Presenter', 'Job set correctly.');
  }

  /**
   * Confirms that we can merge-update a record successfully.
   */
  public function testMergeUpdate() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $result = $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->fields([
        'age' => 31,
        'name' => 'Tiffany',
      ])
      ->execute();

    $this->assertEqual($result, Merge::STATUS_UPDATE, 'Update status returned.');

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge updated properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Tiffany', 'Name set correctly.');
    $this->assertEqual($person->age, 31, 'Age set correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job set correctly.');
  }

  /**
   * Confirms that we can merge-update a record successfully.
   *
   * This test varies from the previous test because it manually defines which
   * fields are inserted, and which fields are updated.
   */
  public function testMergeUpdateExcept() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->insertFields(['age' => 31])
      ->updateFields(['name' => 'Tiffany'])
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge updated properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Tiffany', 'Name set correctly.');
    $this->assertEqual($person->age, 30, 'Age skipped correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job set correctly.');
  }

  /**
   * Confirms that we can merge-update a record, with alternate replacement.
   */
  public function testMergeUpdateExplicit() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->insertFields([
        'age' => 31,
        'name' => 'Tiffany',
      ])
      ->updateFields([
        'name' => 'Joe',
      ])
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge updated properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Joe', 'Name set correctly.');
    $this->assertEqual($person->age, 30, 'Age skipped correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job set correctly.');
  }

  /**
   * Confirms that we can merge-update a record successfully, with expressions.
   */
  public function testMergeUpdateExpression() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $age_before = $this->connection->query('SELECT [age] FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetchField();

    // This is a very contrived example, as I have no idea why you'd want to
    // change age this way, but that's beside the point.
    // Note that we are also double-setting age here, once as a literal and
    // once as an expression. This test will only pass if the expression wins,
    // which is what is supposed to happen.
    $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->fields(['name' => 'Tiffany'])
      ->insertFields(['age' => 31])
      ->expression('age', 'age + :age', [':age' => 4])
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge updated properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Tiffany', 'Name set correctly.');
    $this->assertEqual($person->age, $age_before + 4, 'Age updated correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job set correctly.');
  }

  /**
   * Tests that we can merge-insert without any update fields.
   */
  public function testMergeInsertWithoutUpdate() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $this->connection->merge('test_people')
      ->key('job', 'Presenter')
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Presenter'])->fetch();
    $this->assertEqual($person->name, '', 'Name set correctly.');
    $this->assertEqual($person->age, 0, 'Age set correctly.');
    $this->assertEqual($person->job, 'Presenter', 'Job set correctly.');
  }

  /**
   * Confirms that we can merge-update without any update fields.
   */
  public function testMergeUpdateWithoutUpdate() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge skipped properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Meredith', 'Name skipped correctly.');
    $this->assertEqual($person->age, 30, 'Age skipped correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job skipped correctly.');

    $this->connection->merge('test_people')
      ->key('job', 'Speaker')
      ->insertFields(['age' => 31])
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, 'Merge skipped properly.');

    $person = $this->connection->query('SELECT * FROM {test_people} WHERE [job] = :job', [':job' => 'Speaker'])->fetch();
    $this->assertEqual($person->name, 'Meredith', 'Name skipped correctly.');
    $this->assertEqual($person->age, 30, 'Age skipped correctly.');
    $this->assertEqual($person->job, 'Speaker', 'Job skipped correctly.');
  }

  /**
   * Tests that an invalid merge query throws an exception.
   */
  public function testInvalidMerge() {
    try {
      // This query will fail because there is no key field specified.
      // Normally it would throw an exception but we are suppressing it with
      // the throw_exception option.
      $options['throw_exception'] = FALSE;
      $this->connection->merge('test_people', $options)
        ->fields([
          'age' => 31,
          'name' => 'Tiffany',
        ])
        ->execute();
    }
    catch (InvalidMergeQueryException $e) {
      $this->fail('$options[\'throw_exception\'] is FALSE, but InvalidMergeQueryException thrown for invalid query.');
    }

    try {
      // This query will fail because there is no key field specified.
      $this->connection->merge('test_people')
        ->fields([
          'age' => 31,
          'name' => 'Tiffany',
        ])
        ->execute();
      $this->fail('InvalidMergeQueryException should be thrown.');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(InvalidMergeQueryException::class, $e);
    }
  }

  /**
   * Tests that we can merge-insert with reserved keywords.
   */
  public function testMergeWithReservedWords() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {select}')->fetchField();

    $this->connection->merge('select')
      ->key('id', 2)
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {select}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    $person = $this->connection->query('SELECT * FROM {select} WHERE [id] = :id', [':id' => 2])->fetch();
    $this->assertEquals('', $person->update);
    $this->assertEquals('2', $person->id);
  }

}
