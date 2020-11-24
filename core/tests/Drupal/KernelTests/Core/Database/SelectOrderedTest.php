<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the Select query builder.
 *
 * @group Database
 */
class SelectOrderedTest extends DatabaseTestBase {

  /**
   * Tests basic ORDER BY.
   */
  public function testSimpleSelectOrdered() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy($age_field);
    $result = $query->execute();

    $num_records = 0;
    $last_age = 0;
    foreach ($result as $record) {
      $num_records++;
      // Verify that the results are returned in the correct order.
      $this->assertGreaterThanOrEqual($last_age, $record->age);
      $last_age = $record->age;
    }

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

  /**
   * Tests multiple ORDER BY.
   */
  public function testSimpleSelectMultiOrdered() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $job_field = $query->addField('test', 'job');
    $query->orderBy($job_field);
    $query->orderBy($age_field);
    $result = $query->execute();

    $num_records = 0;
    $expected = [
      ['Ringo', 28, 'Drummer'],
      ['John', 25, 'Singer'],
      ['George', 27, 'Singer'],
      ['Paul', 26, 'Songwriter'],
    ];
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
  public function testSimpleSelectOrderedDesc() {
    $query = $this->connection->select('test');
    $query->addField('test', 'name');
    $age_field = $query->addField('test', 'age', 'age');
    $query->orderBy($age_field, 'DESC');
    $result = $query->execute();

    $num_records = 0;
    $last_age = 100000000;
    foreach ($result as $record) {
      $num_records++;
      // Verify that the results are returned in the correct order.
      $this->assertLessThanOrEqual($last_age, $record->age);
      $last_age = $record->age;
    }

    $this->assertEqual($num_records, 4, 'Returned the correct number of rows.');
  }

}
