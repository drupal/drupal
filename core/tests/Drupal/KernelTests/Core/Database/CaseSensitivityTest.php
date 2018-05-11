<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests handling case sensitive collation.
 *
 * @group Database
 */
class CaseSensitivityTest extends DatabaseTestBase {

  /**
   * Tests BINARY collation in MySQL.
   */
  public function testCaseSensitiveInsert() {
    $num_records_before = db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    db_insert('test')
      ->fields([
        // A record already exists with name 'John'.
        'name' => 'john',
        'age' => 2,
        'job' => 'Baby',
      ])
      ->execute();

    $num_records_after = db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 1, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', [':name' => 'john'])->fetchField();
    $this->assertIdentical($saved_age, '2', 'Can retrieve after inserting.');
  }

}
