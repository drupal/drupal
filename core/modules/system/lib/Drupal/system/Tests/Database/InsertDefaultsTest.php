<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\InsertDefaultsTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Query\NoFieldsException;

/**
 * Insert tests for "database default" values.
 */
class InsertDefaultsTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Insert tests, default fields',
      'description' => 'Test the Insert query builder with default values.',
      'group' => 'Database',
    );
  }

  /**
   * Test that we can run a query that is "default values for everything".
   */
  function testDefaultInsert() {
    $query = db_insert('test')->useDefaults(array('job'));
    $id = $query->execute();

    $schema = drupal_get_schema('test');

    $job = db_query('SELECT job FROM {test} WHERE id = :id', array(':id' => $id))->fetchField();
    $this->assertEqual($job, $schema['fields']['job']['default'], t('Default field value is set.'));
  }

  /**
   * Test that no action will be preformed if no fields are specified.
   */
  function testDefaultEmptyInsert() {
    $num_records_before = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();

    try {
      $result = db_insert('test')->execute();
      // This is only executed if no exception has been thrown.
      $this->fail(t('Expected exception NoFieldsException has not been thrown.'));
    } catch (NoFieldsException $e) {
      $this->pass(t('Expected exception NoFieldsException has been thrown.'));
    }

    $num_records_after = (int) db_query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertIdentical($num_records_before, $num_records_after, t('Do nothing as no fields are specified.'));
  }

  /**
   * Test that we can insert fields with values and defaults in the same query.
   */
  function testDefaultInsertWithFields() {
    $query = db_insert('test')
      ->fields(array('name' => 'Bob'))
      ->useDefaults(array('job'));
    $id = $query->execute();

    $schema = drupal_get_schema('test');

    $job = db_query('SELECT job FROM {test} WHERE id = :id', array(':id' => $id))->fetchField();
    $this->assertEqual($job, $schema['fields']['job']['default'], t('Default field value is set.'));
  }
}
