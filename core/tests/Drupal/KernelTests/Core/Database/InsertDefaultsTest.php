<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Query\NoFieldsException;

/**
 * Tests the Insert query builder with default values.
 *
 * @group Database
 */
class InsertDefaultsTest extends DatabaseTestBase {

  /**
   * Tests that we can run a query that uses default values for everything.
   */
  public function testDefaultInsert() {
    $query = db_insert('test')->useDefaults(['job']);
    $id = $query->execute();

    $schema = drupal_get_module_schema('database_test', 'test');

    $job = db_query('SELECT job FROM {test} WHERE id = :id', [':id' => $id])->fetchField();
    $this->assertEqual($job, $schema['fields']['job']['default'], 'Default field value is set.');
  }

  /**
   * Tests that no action will be preformed if no fields are specified.
   */
  public function testDefaultEmptyInsert() {
    $num_records_before = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    try {
      db_insert('test')->execute();
      // This is only executed if no exception has been thrown.
      $this->fail('Expected exception NoFieldsException has not been thrown.');
    }
    catch (NoFieldsException $e) {
      $this->pass('Expected exception NoFieldsException has been thrown.');
    }

    $num_records_after = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before, $num_records_after, 'Do nothing as no fields are specified.');
  }

  /**
   * Tests that we can insert fields with values and defaults in the same query.
   */
  public function testDefaultInsertWithFields() {
    $query = db_insert('test')
      ->fields(['name' => 'Bob'])
      ->useDefaults(['job']);
    $id = $query->execute();

    $schema = drupal_get_module_schema('database_test', 'test');

    $job = db_query('SELECT job FROM {test} WHERE id = :id', [':id' => $id])->fetchField();
    $this->assertEqual($job, $schema['fields']['job']['default'], 'Default field value is set.');
  }

}
