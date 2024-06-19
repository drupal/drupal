<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * Tests the update query builder.
 *
 * @group Database
 */
class UpdateTest extends DatabaseTestBase {

  /**
   * Confirms that we can update a single record successfully.
   */
  public function testSimpleUpdate(): void {
    $num_updated = $this->connection->update('test')
      ->fields(['name' => 'Tiffany'])
      ->condition('id', 1)
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $saved_name = $this->connection->query('SELECT [name] FROM {test} WHERE [id] = :id', [':id' => 1])->fetchField();
    $this->assertSame('Tiffany', $saved_name, 'Updated name successfully.');
  }

  /**
   * Confirms updating to NULL.
   */
  public function testSimpleNullUpdate(): void {
    $this->ensureSampleDataNull();
    $num_updated = $this->connection->update('test_null')
      ->fields(['age' => NULL])
      ->condition('name', 'Kermit')
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $saved_age = $this->connection->query('SELECT [age] FROM {test_null} WHERE [name] = :name', [':name' => 'Kermit'])->fetchField();
    $this->assertNull($saved_age, 'Updated name successfully.');
  }

  /**
   * Confirms that we can update multiple records successfully.
   */
  public function testMultiUpdate(): void {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('job', 'Singer')
      ->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Confirms that we can update multiple records with a non-equality condition.
   */
  public function testMultiGTUpdate(): void {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->condition('age', 26, '>')
      ->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Confirms that we can update multiple records with a where call.
   */
  public function testWhereUpdate(): void {
    $num_updated = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->where('[age] > :age', [':age' => 26])
      ->execute();
    $this->assertSame(2, $num_updated, 'Updated 2 records.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('2', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Confirms that we can stack condition and where calls.
   */
  public function testWhereAndConditionUpdate(): void {
    $update = $this->connection->update('test')
      ->fields(['job' => 'Musician'])
      ->where('[age] > :age', [':age' => 26])
      ->condition('name', 'Ringo');
    $num_updated = $update->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 record.');

    $num_matches = $this->connection->query('SELECT COUNT(*) FROM {test} WHERE [job] = :job', [':job' => 'Musician'])->fetchField();
    $this->assertSame('1', $num_matches, 'Updated fields successfully.');
  }

  /**
   * Tests updating with expressions.
   */
  public function testExpressionUpdate(): void {
    // Ensure that expressions are handled properly. This should set every
    // record's age to a square of itself.
    $num_rows = $this->connection->update('test')
      ->expression('age', '[age] * [age]')
      ->execute();
    $this->assertSame(4, $num_rows, 'Updated 4 records.');

    $saved_name = $this->connection->query('SELECT [name] FROM {test} WHERE [age] = :age', [':age' => pow(26, 2)])->fetchField();
    $this->assertSame('Paul', $saved_name, 'Successfully updated values using an algebraic expression.');
  }

  /**
   * Tests return value on update.
   */
  public function testUpdateAffectedRows(): void {
    // At 5am in the morning, all band members but those with a priority 1 task
    // are sleeping. So we set their tasks to 'sleep'. 5 records match the
    // condition and therefore are affected by the query, even though two of
    // them actually don't have to be changed because their value was already
    // 'sleep'. Still, execute() should return 5 affected rows, not only 3,
    // because that's cross-db expected behavior.
    $num_rows = $this->connection->update('test_task')
      ->condition('priority', 1, '<>')
      ->fields(['task' => 'sleep'])
      ->execute();
    $this->assertSame(5, $num_rows, 'Correctly returned 5 affected rows.');
  }

  /**
   * Confirm that we can update values in a column with special name.
   */
  public function testSpecialColumnUpdate(): void {
    $num_updated = $this->connection->update('select')
      ->fields([
        'update' => 'New update value',
      ])
      ->condition('id', 1)
      ->execute();
    $this->assertSame(1, $num_updated, 'Updated 1 special column record.');

    $saved_value = $this->connection->query('SELECT [update] FROM {select} WHERE [id] = :id', [':id' => 1])->fetchField();
    $this->assertEquals('New update value', $saved_value);
  }

  /**
   * Updating a not existing table throws a DatabaseExceptionWrapper.
   */
  public function testUpdateNonExistingTable(): void {
    $this->expectException(DatabaseExceptionWrapper::class);
    $this->connection->update('a-table-that-does-not-exist')
      ->fields([
        'update' => 'New update value',
      ])
      ->condition('id', 1)
      ->execute();
  }

  /**
   * Updating a serial field throws a IntegrityConstraintViolationException.
   */
  public function testUpdateValueInSerial(): void {
    $this->expectException(IntegrityConstraintViolationException::class);
    $this->connection->update('test')
      ->fields(['id' => 2])
      ->condition('id', 1)
      ->execute();
  }

}
