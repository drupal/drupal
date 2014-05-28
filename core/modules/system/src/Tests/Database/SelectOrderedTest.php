<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\SelectOrderedTest.
 */

namespace Drupal\system\Tests\Database;

/**
 * Tests SELECT with ORDER BY clauses.
 */
class SelectOrderedTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Select tests, ordered',
      'description' => 'Test the Select query builder.',
      'group' => 'Database',
    );
  }

  /**
   * Tests basic ORDER BY.
   */
  function testSimpleSelectOrdered() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy($age_field);
    $result = $query->execute();

    $num_records = 0;
    $last_age = 0;
    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue($record->age >= $last_age, 'Results returned in correct order.');
      $last_age = $record->age;
    }

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

  /**
   * Tests multiple ORDER BY.
   */
  function testSimpleSelectMultiOrdered() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $job_field = $query->addField('test', 'job');
    $query->orderBy($job_field);
    $query->orderBy($age_field);
    $result = $query->execute();

    $num_records = 0;
    $expected = array(
      array('Ringo', 28, 'Drummer'),
      array('John', 25, 'Singer'),
      array('George', 27, 'Singer'),
      array('Paul', 26, 'Songwriter'),
    );
    $results = $result->fetchAll(\PDO::FETCH_NUM);
    foreach ($expected as $k => $record) {
      $num_records++;
      foreach ($record as $kk => $col) {
        if ($expected[$k][$kk] != $results[$k][$kk]) {
          $this->assertTrue(FALSE, 'Results returned in correct order.');
        }
      }
    }
    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

  /**
   * Tests ORDER BY descending.
   */
  function testSimpleSelectOrderedDesc() {
    $query = db_select('test');
    $name_field = $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy($age_field, 'DESC');
    $result = $query->execute();

    $num_records = 0;
    $last_age = 100000000;
    foreach ($result as $record) {
      $num_records++;
      $this->assertTrue($record->age <= $last_age, 'Results returned in correct order.');
      $last_age = $record->age;
    }

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }
}
