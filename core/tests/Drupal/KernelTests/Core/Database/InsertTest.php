<?php

namespace Drupal\KernelTests\Core\Database;

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
    $this->assertIdentical($query->count(), 1, 'One record is queued for insertion.');
    $query->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 1, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Yoko'])->fetchField();
    $this->assertIdentical($saved_age, '29', 'Can retrieve after inserting.');
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
    $this->assertIdentical($query->count(), 2, 'Two records are queued for insertion.');

    // We should be able to say "use the field order".
    // This is not the recommended mechanism for most cases, but it should work.
    $query->values(['Moe', '32']);

    // Check how many records are queued for insertion.
    $this->assertIdentical($query->count(), 3, 'Three records are queued for insertion.');
    $query->execute();

    $num_records_after = (int) $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 3, $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
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
    $this->assertIdentical($query->count(), 1, 'One record is queued for insertion.');
    // This should run the insert, but leave the fields intact.
    $query->execute();

    // We should be able to specify values in any order if named.
    $query->values([
      'age' => '31',
      'name' => 'Curly',
    ]);
    // Check how many records are queued for insertion.
    $this->assertIdentical($query->count(), 1, 'One record is queued for insertion.');
    $query->execute();

    // We should be able to say "use the field order".
    $query->values(['Moe', '32']);

    // Check how many records are queued for insertion.
    $this->assertIdentical($query->count(), 1, 'One record is queued for insertion.');
    $query->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame((int) $num_records_before + 3, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Larry'])->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
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
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Curly'])->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'Moe'])->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
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

    $this->assertIdentical($id, '5', 'Auto-increment ID returned successfully.');
  }

  /**
   * Tests that the INSERT INTO ... SELECT (fields) ... syntax works.
   */
  public function testInsertSelectFields() {
    $query = $this->connection->select('test_people', 'tp');
    // The query builder will always append expressions after fields.
    // Add the expression first to test that the insert fields are correctly
    // re-ordered.
    $query->addExpression('tp.age', 'age');
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
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
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
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
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

}
