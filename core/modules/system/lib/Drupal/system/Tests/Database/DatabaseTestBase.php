<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\DatabaseTestBase.
 */

namespace Drupal\system\Tests\Database;

use Drupal\simpletest\WebTestBase;

/**
 * Base test class for databases.
 *
 * Because all database tests share the same test data, we can centralize that
 * here.
 */
class DatabaseTestBase extends WebTestBase {
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'database_test';
    parent::setUp($modules);

    $schema['test'] = drupal_get_schema('test');
    $schema['test_people'] = drupal_get_schema('test_people');
    $schema['test_one_blob'] = drupal_get_schema('test_one_blob');
    $schema['test_two_blobs'] = drupal_get_schema('test_two_blobs');
    $schema['test_task'] = drupal_get_schema('test_task');

    $this->installTables($schema);

    $this->addSampleData();
  }

  /**
   * Set up several tables needed by a certain test.
   *
   * @param $schema
   *   An array of table definitions to install.
   */
  function installTables($schema) {
    // This ends up being a test for table drop and create, too, which is nice.
    foreach ($schema as $name => $data) {
      if (db_table_exists($name)) {
        db_drop_table($name);
      }
      db_create_table($name, $data);
    }

    foreach ($schema as $name => $data) {
      $this->assertTrue(db_table_exists($name), t('Table @name created successfully.', array('@name' => $name)));
    }
  }

  /**
   * Set up tables for NULL handling.
   */
  function ensureSampleDataNull() {
    $schema['test_null'] = drupal_get_schema('test_null');
    $this->installTables($schema);

    db_insert('test_null')
    ->fields(array('name', 'age'))
    ->values(array(
      'name' => 'Kermit',
      'age' => 25,
    ))
    ->values(array(
      'name' => 'Fozzie',
      'age' => NULL,
    ))
    ->values(array(
      'name' => 'Gonzo',
      'age' => 27,
    ))
    ->execute();
  }

  /**
   * Setup our sample data.
   *
   * These are added using db_query(), since we're not trying to test the
   * INSERT operations here, just populate.
   */
  function addSampleData() {
    // We need the IDs, so we can't use a multi-insert here.
    $john = db_insert('test')
      ->fields(array(
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
      ))
      ->execute();

    $george = db_insert('test')
      ->fields(array(
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
      ))
      ->execute();

    $ringo = db_insert('test')
      ->fields(array(
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
      ))
      ->execute();

    $paul = db_insert('test')
      ->fields(array(
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
      ))
      ->execute();

    db_insert('test_people')
      ->fields(array(
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
      ))
      ->execute();

    db_insert('test_task')
      ->fields(array('pid', 'task', 'priority'))
      ->values(array(
        'pid' => $john,
        'task' => 'eat',
        'priority' => 3,
      ))
      ->values(array(
        'pid' => $john,
        'task' => 'sleep',
        'priority' => 4,
      ))
      ->values(array(
        'pid' => $john,
        'task' => 'code',
        'priority' => 1,
      ))
      ->values(array(
        'pid' => $george,
        'task' => 'sing',
        'priority' => 2,
      ))
      ->values(array(
        'pid' => $george,
        'task' => 'sleep',
        'priority' => 2,
      ))
      ->values(array(
        'pid' => $paul,
        'task' => 'found new band',
        'priority' => 1,
      ))
      ->values(array(
        'pid' => $paul,
        'task' => 'perform at superbowl',
        'priority' => 3,
      ))
      ->execute();
  }
}
