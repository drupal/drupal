<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\InsertTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests the insert builder.
 */
class InsertTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Insert tests',
      'description' => 'Test the Insert query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Tests very basic insert functionality.
   */
  function testSimpleInsert() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = db_insert('test');
    $query->fields(array(
      'name' => 'Yoko',
      'age' => '29',
    ));
    $query->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertIdentical($num_records_before + 1, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Yoko'))->fetchField();
    $this->assertIdentical($saved_age, '29', 'Can retrieve after inserting.');
  }

  /**
   * Tests that we can insert multiple records in one query object.
   */
  function testMultiInsert() {
    $num_records_before = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = db_insert('test');
    $query->fields(array(
      'name' => 'Larry',
      'age' => '30',
    ));

    // We should be able to specify values in any order if named.
    $query->values(array(
      'age' => '31',
      'name' => 'Curly',
    ));

    // We should be able to say "use the field order".
    // This is not the recommended mechanism for most cases, but it should work.
    $query->values(array('Moe', '32'));
    $query->execute();

    $num_records_after = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertIdentical($num_records_before + 3, $num_records_after, 'Record inserts correctly.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Larry'))->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Curly'))->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Moe'))->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
  }

  /**
   * Tests that an insert object can be reused with new data after it executes.
   */
  function testRepeatedInsert() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    $query = db_insert('test');

    $query->fields(array(
      'name' => 'Larry',
      'age' => '30',
    ));
    $query->execute();  // This should run the insert, but leave the fields intact.

    // We should be able to specify values in any order if named.
    $query->values(array(
      'age' => '31',
      'name' => 'Curly',
    ));
    $query->execute();

    // We should be able to say "use the field order".
    $query->values(array('Moe', '32'));
    $query->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertIdentical((int) $num_records_before + 3, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Larry'))->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Curly'))->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Moe'))->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
  }

  /**
   * Tests that we can specify fields without values and specify values later.
   */
  function testInsertFieldOnlyDefinintion() {
    // This is useful for importers, when we want to create a query and define
    // its fields once, then loop over a multi-insert execution.
    db_insert('test')
      ->fields(array('name', 'age'))
      ->values(array('Larry', '30'))
      ->values(array('Curly', '31'))
      ->values(array('Moe', '32'))
      ->execute();
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Larry'))->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Curly'))->fetchField();
    $this->assertIdentical($saved_age, '31', 'Can retrieve after inserting.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Moe'))->fetchField();
    $this->assertIdentical($saved_age, '32', 'Can retrieve after inserting.');
  }

  /**
   * Tests that inserts return the proper auto-increment ID.
   */
  function testInsertLastInsertID() {
    $id = db_insert('test')
      ->fields(array(
        'name' => 'Larry',
        'age' => '30',
      ))
      ->execute();

    $this->assertIdentical($id, '5', 'Auto-increment ID returned successfully.');
  }

  /**
   * Tests that the INSERT INTO ... SELECT (fields) ... syntax works.
   */
  function testInsertSelectFields() {
    $query = db_select('test_people', 'tp');
    // The query builder will always append expressions after fields.
    // Add the expression first to test that the insert fields are correctly
    // re-ordered.
    $query->addExpression('tp.age', 'age');
    $query
      ->fields('tp', array('name','job'))
      ->condition('tp.name', 'Meredith');

    // The resulting query should be equivalent to:
    // INSERT INTO test (age, name, job)
    // SELECT tp.age AS age, tp.name AS name, tp.job AS job
    // FROM test_people tp
    // WHERE tp.name = 'Meredith'
    db_insert('test')
      ->from($query)
      ->execute();

    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Meredith'))->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
  }

  /**
   * Tests that the INSERT INTO ... SELECT * ... syntax works.
   */
  function testInsertSelectAll() {
    $query = db_select('test_people', 'tp')
      ->fields('tp')
      ->condition('tp.name', 'Meredith');

    // The resulting query should be equivalent to:
    // INSERT INTO test_people_copy
    // SELECT *
    // FROM test_people tp
    // WHERE tp.name = 'Meredith'
    db_insert('test_people_copy')
      ->from($query)
      ->execute();

    $saved_age = db_query('SELECT age FROM {test_people_copy} WHERE name = :name', array(':name' => 'Meredith'))->fetchField();
    $this->assertIdentical($saved_age, '30', 'Can retrieve after inserting.');
  }

}
