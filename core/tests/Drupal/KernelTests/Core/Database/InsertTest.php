<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * Tests the insert builder.
 *
 * @group Database
 */
class InsertTest extends DatabaseTestBase {

  /**
   * Tests very basic insert functionality.
   */
  public function testSimpleInsert() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = $this->connection->insert('test');
    $query->fields([
      'name' => 'Yoko',
      'age' => '29',
    ]);

    // Check how many records are queued for insertion.
    $this->assertCount(1, $query, 'One record is queued for insertion.');
    $query->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 1, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Yoko'])->fetchField();
    $this->assertSame('29', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that we can insert multiple records in one query object.
   */
  public function testMultiInsert() {
    $num_records_before = (int) $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = $this->connection->insert('test');
    $query->fields([
      'name' => 'Larry',
      'age' => '30',
    ]);

    // We should be able to specify values in any order if named.
    $query->values([
      'age' => '31',
      'name' => 'Curly',
    ]);

    // Check how many records are queued for insertion.
    $this->assertCount(2, $query, 'Two records are queued for insertion.');

    // We should be able to say "use the field order".
    // This is not the recommended mechanism for most cases, but it should work.
    $query->values(['Moe', '32']);

    // Check how many records are queued for insertion.
    $this->assertCount(3, $query, 'Three records are queued for insertion.');
    $query->execute();

    $num_records_after = (int) $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 3, $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField();
    $this->assertSame('30', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertSame('31', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertSame('32', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that an insert object can be reused with new data after it executes.
   */
  public function testRepeatedInsert() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = $this->connection->insert('test');

    $query->fields([
      'name' => 'Larry',
      'age' => '30',
    ]);
    // Check how many records are queued for insertion.
    $this->assertCount(1, $query, 'One record is queued for insertion.');
    // This should run the insert, but leave the fields intact.
    $query->execute();

    // We should be able to specify values in any order if named.
    $query->values([
      'age' => '31',
      'name' => 'Curly',
    ]);
    // Check how many records are queued for insertion.
    $this->assertCount(1, $query, 'One record is queued for insertion.');
    $query->execute();

    // We should be able to say "use the field order".
    $query->values(['Moe', '32']);

    // Check how many records are queued for insertion.
    $this->assertCount(1, $query, 'One record is queued for insertion.');
    $query->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame((int) $num_records_before + 3, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField();
    $this->assertSame('30', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertSame('31', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertSame('32', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that we can specify fields without values and specify values later.
   */
  public function testInsertFieldOnlyDefinition() {
    // This is useful for importers, when we want to create a query and define
    // its fields once, then loop over a multi-insert execution.
    $this->connection->insert('test')
      ->fields(['name', 'age'])
      ->values(['Larry', '30'])
      ->values(['Curly', '31'])
      ->values(['Moe', '32'])
      ->execute();
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField();
    $this->assertSame('30', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertSame('31', $saved_age, 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertSame('32', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that inserts return the proper auto-increment ID.
   */
  public function testInsertLastInsertID() {
    $id = $this->connection->insert('test')
      ->fields([
        'name' => 'Larry',
        'age' => '30',
      ])
      ->execute();

    $this->assertSame('5', $id, 'Auto-increment ID returned successfully.');
  }

  /**
   * Tests that the INSERT INTO ... SELECT (fields) ... syntax works.
   */
  public function testInsertSelectFields() {
    $query = $this->connection->select('test_people', 'tp');
    // The query builder will always append expressions after fields.
    // Add the expression first to test that the insert fields are correctly
    // re-ordered.
    $query->addExpression('[tp].[age]', 'age');
    $query
      ->fields('tp', ['name', 'job'])
      ->condition('tp.name', 'Meredith');

    // The resulting query should be equivalent to:
    // INSERT INTO test (age, name, job)
    // SELECT tp.age AS age, tp.name AS name, tp.job AS job
    // FROM test_people tp
    // WHERE tp.name = 'Meredith'
    $this->connection->insert('test')
      ->from($query)
      ->execute();

    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Meredith'])->fetchField();
    $this->assertSame('30', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that the INSERT INTO ... SELECT * ... syntax works.
   */
  public function testInsertSelectAll() {
    $query = $this->connection->select('test_people', 'tp')
      ->fields('tp')
      ->condition('tp.name', 'Meredith');

    // The resulting query should be equivalent to:
    // INSERT INTO test_people_copy
    // SELECT *
    // FROM test_people tp
    // WHERE tp.name = 'Meredith'
    $this->connection->insert('test_people_copy')
      ->from($query)
      ->execute();

    $saved_age = $this->connection->query('SELECT [age] FROM {test_people_copy} WHERE [name] = :name', [':name' => 'Meredith'])->fetchField();
    $this->assertSame('30', $saved_age, 'Can retrieve after inserting.');
  }

  /**
   * Tests that we can INSERT INTO a special named column.
   */
  public function testSpecialColumnInsert() {
    $this->connection->insert('select')
      ->fields([
        'id' => 2,
        'update' => 'Update value 2',
      ])
      ->execute();
    $saved_value = $this->connection->query('SELECT [update] FROM {select} WHERE [id] = :id', [':id' => 2])->fetchField();
    $this->assertEquals('Update value 2', $saved_value);
  }

  /**
   * Tests insertion integrity violation with no default value for a column.
   */
  public function testInsertIntegrityViolation() {
    // Remove the default from the 'age' column, so that inserting a record
    // without its value specified will lead to integrity failure.
    $this->connection->schema()->changeField('test', 'age', 'age', [
      'description' => "The person's age",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
    ]);

    // Try inserting a record that misses the value for the 'age' column,
    // should raise an IntegrityConstraintViolationException.
    $this->expectException(IntegrityConstraintViolationException::class);
    $this->connection->insert('test')
      ->fields(['name'])
      ->values(['name' => 'Elvis'])
      ->execute();
  }

}
