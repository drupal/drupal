<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\UpdateComplexTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests for more complex update statements.
 */
class UpdateComplexTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Update tests, Complex',
      'description' => 'Test the Update query builder, complex queries.',
      'group' => 'Database',
    );
  }

  /**
   * Test updates with OR conditionals.
   */
  function testOrConditionUpdate() {
    $update = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition(db_or()
        ->condition('name', 'John')
        ->condition('name', 'Paul')
      );
    $num_updated = $update->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Test WHERE IN clauses.
   */
  function testInConditionUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('name', array('John', 'Paul'), 'IN')
      ->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Test WHERE NOT IN clauses.
   */
  function testNotInConditionUpdate() {
    // The o is lowercase in the 'NoT IN' operator, to make sure the operators
    // work in mixed case.
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('name', array('John', 'Paul', 'George'), 'NoT IN')
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '1', t('Updated fields successfully.'));
  }

  /**
   * Test BETWEEN conditional clauses.
   */
  function testBetweenConditionUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('age', array(25, 26), 'BETWEEN')
      ->execute();
    $this->assertIdentical($num_updated, 2, t('Updated 2 records.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '2', t('Updated fields successfully.'));
  }

  /**
   * Test LIKE conditionals.
   */
  function testLikeConditionUpdate() {
    $num_updated = db_update('test')
      ->fields(array('job' => 'Musician'))
      ->condition('name', '%ge%', 'LIKE')
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '1', t('Updated fields successfully.'));
  }

  /**
   * Test update with expression values.
   */
  function testUpdateExpression() {
    $before_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetchField();
    $GLOBALS['larry_test'] = 1;
    $num_updated = db_update('test')
      ->condition('name', 'Ringo')
      ->fields(array('job' => 'Musician'))
      ->expression('age', 'age + :age', array(':age' => 4))
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $num_matches = db_query('SELECT COUNT(*) FROM {test} WHERE job = :job', array(':job' => 'Musician'))->fetchField();
    $this->assertIdentical($num_matches, '1', t('Updated fields successfully.'));

    $person = db_query('SELECT * FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetch();
    $this->assertEqual($person->name, 'Ringo', t('Name set correctly.'));
    $this->assertEqual($person->age, $before_age + 4, t('Age set correctly.'));
    $this->assertEqual($person->job, 'Musician', t('Job set correctly.'));
    $GLOBALS['larry_test'] = 0;
  }

  /**
   * Test update with only expression values.
   */
  function testUpdateOnlyExpression() {
    $before_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetchField();
    $num_updated = db_update('test')
      ->condition('name', 'Ringo')
      ->expression('age', 'age + :age', array(':age' => 4))
      ->execute();
    $this->assertIdentical($num_updated, 1, t('Updated 1 record.'));

    $after_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'Ringo'))->fetchField();
    $this->assertEqual($before_age + 4, $after_age, t('Age updated correctly'));
  }
}
