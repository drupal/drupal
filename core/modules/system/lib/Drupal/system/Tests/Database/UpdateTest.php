<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\UpdateTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Update builder tests.
 */
class UpdateTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Update tests',
      'description' => 'Test the Update query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Confirm that we can update a single record successfully.
   */
  function testSimpleUpdate() {
    $num_updated = db_update('test')
      ->fields(array('name' => 'Tiffany'))
      ->condition('id', 1)
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $saved_name = db_query('SELECT name FROM {test} WHERE id = :id', array(':id' => 1))->fetchField();
    $this->assertIdentical($saved_name, 'Tiffany', t('Updated name successfully.'));
  }

  /**
   * Confirm updating to NULL.
   */
  function testSimpleNullUpdate() {
    $this->ensureSampleDataNull();
    $num_updated = db_update('test_null')
      ->fields(array('age' => NULL))
      ->condition('name', 'Kermit')
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $saved_age = db_query('SELECT age FROM {test_null} WHERE name = :name', array(':name' => 'Kermit'))->fetchField();
    $this->assertNull($saved_age, t('Updated name successfully.'));
  }

  /**
   * Confirm that we can update a multiple records successfully.
   */
  function testMultiUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('job', 'Singer')
      ->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Confirm that we can update a multiple records with a non-equality condition.
   */
  function testMultiGTUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('age', 26, '>')
      ->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Confirm that we can update a multiple records with a where call.
   */
  function testWhereUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->where('age > :age', array(':age' => 26))
      ->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Confirm that we can stack condition and where calls.
   */
  function testWhereAndConditionUpdate() {
    $update = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->where('age > :age', array(':age' => 26))
      ->condition('name', 'Ringo');
    $num_updated = $update->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '1', t('Updated fields successfully.'));
  }

  /**
   * Test updating with expressions.
   */
  function testExpressionUpdate() {
    // Set age = 1 for a single row for this test to work.
    db_update('test')
      ->condition('id', 1)
      ->fields(array('age' => 1))
      ->execute();

    // Ensure that expressions are handled properly.  This should set every
    // record's age to a square of itself, which will change only three of the
    // four records in the table since 1*1 = 1. That means only three records
    // are modified, so we should get back 3, not 4, from execute().
    $num_rows = db_update('test')
      ->expression('age', 'age * age')
      ->execute();
    $this->assertIdentical($num_rows, 3, t('Number of affected rows are returned.'));
  }
}
