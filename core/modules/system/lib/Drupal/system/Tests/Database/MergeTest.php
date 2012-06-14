<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\MergeTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\InvalidMergeQueryException;

/**
 * Test the MERGE query builder.
 */
class MergeTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Merge tests',
      'description' => 'Test the Merge query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Confirm that we can merge-insert a record successfully.
   */
  function testMergeInsert() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $result = db_merge('test_people')
      ->key(array('job' => 'Presenter'))
      ->fields(array(
        'age' => 31,
        'name' => 'Tiffany',
      ))
      ->execute();

    $this->assertEqual($result, Merge::STATUS_INSERT, t('Insert status returned.'));

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before + 1, $num_records_after, t('Merge inserted properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Presenter'))->fetch();
    $this->assertEqual($person->name, 'Tiffany', t('Name set correctly.'));
    $this->assertEqual($person->age, 31, t('Age set correctly.'));
    $this->assertEqual($person->job, 'Presenter', t('Job set correctly.'));
  }

  /**
   * Confirm that we can merge-update a record successfully.
   */
  function testMergeUpdate() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $result = db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->fields(array(
        'age' => 31,
        'name' => 'Tiffany',
      ))
      ->execute();

    $this->assertEqual($result, Merge::STATUS_UPDATE, t('Update status returned.'));

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge updated properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Tiffany', t('Name set correctly.'));
    $this->assertEqual($person->age, 31, t('Age set correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job set correctly.'));
  }

  /**
   * Confirm that we can merge-update a record successfully, with different insert and update.
   */
  function testMergeUpdateExcept() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->insertFields(array('age' => 31))
      ->updateFields(array('name' => 'Tiffany'))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge updated properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Tiffany', t('Name set correctly.'));
    $this->assertEqual($person->age, 30, t('Age skipped correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job set correctly.'));
  }

  /**
   * Confirm that we can merge-update a record successfully, with alternate replacement.
   */
  function testMergeUpdateExplicit() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->insertFields(array(
        'age' => 31,
        'name' => 'Tiffany',
      ))
      ->updateFields(array(
        'name' => 'Joe',
      ))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge updated properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Joe', t('Name set correctly.'));
    $this->assertEqual($person->age, 30, t('Age skipped correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job set correctly.'));
  }

  /**
   * Confirm that we can merge-update a record successfully, with expressions.
   */
  function testMergeUpdateExpression() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    $age_before = db_query('SELECT age FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetchField();

    // This is a very contrived example, as I have no idea why you'd want to
    // change age this way, but that's beside the point.
    // Note that we are also double-setting age here, once as a literal and
    // once as an expression. This test will only pass if the expression wins,
    // which is what is supposed to happen.
    db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->fields(array('name' => 'Tiffany'))
      ->insertFields(array('age' => 31))
      ->expression('age', 'age + :age', array(':age' => 4))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge updated properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Tiffany', t('Name set correctly.'));
    $this->assertEqual($person->age, $age_before + 4, t('Age updated correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job set correctly.'));
  }

  /**
   * Test that we can merge-insert without any update fields.
   */
  function testMergeInsertWithoutUpdate() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    db_merge('test_people')
      ->key(array('job' => 'Presenter'))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before + 1, $num_records_after, t('Merge inserted properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Presenter'))->fetch();
    $this->assertEqual($person->name, '', t('Name set correctly.'));
    $this->assertEqual($person->age, 0, t('Age set correctly.'));
    $this->assertEqual($person->job, 'Presenter', t('Job set correctly.'));
  }

  /**
   * Confirm that we can merge-update without any update fields.
   */
  function testMergeUpdateWithoutUpdate() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();

    db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge skipped properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Meredith', t('Name skipped correctly.'));
    $this->assertEqual($person->age, 30, t('Age skipped correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job skipped correctly.'));

    db_merge('test_people')
      ->key(array('job' => 'Speaker'))
      ->insertFields(array('age' => 31))
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test_people}')->fetchField();
    $this->assertEqual($num_records_before, $num_records_after, t('Merge skipped properly.'));

    $person = db_query('SELECT * FROM {test_people} WHERE job = :job', array(':job' => 'Speaker'))->fetch();
    $this->assertEqual($person->name, 'Meredith', t('Name skipped correctly.'));
    $this->assertEqual($person->age, 30, t('Age skipped correctly.'));
    $this->assertEqual($person->job, 'Speaker', t('Job skipped correctly.'));
  }

  /**
   * Test that an invalid merge query throws an exception like it is supposed to.
   */
  function testInvalidMerge() {
    try {
      // This query should die because there is no key field specified.
      db_merge('test_people')
        ->fields(array(
          'age' => 31,
          'name' => 'Tiffany',
        ))
        ->execute();
    }
    catch (InvalidMergeQueryException $e) {
      $this->pass(t('InvalidMergeQueryException thrown for invalid query.'));
      return;
    }
    $this->fail(t('No InvalidMergeQueryException thrown'));
  }
}
